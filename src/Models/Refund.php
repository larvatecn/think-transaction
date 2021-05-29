<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Transaction\Models;

use Carbon\CarbonInterface;
use Exception;
use Larva\Transaction\Events\RefundFailure;
use Larva\Transaction\Events\RefundSuccess;
use Larva\Transaction\Transaction;
use think\facade\Event;
use think\facade\Log;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;
use think\model\relation\MorphTo;

/**
 * 退款处理模型
 * @property string $id
 * @property int $user_id
 * @property int $charge_id
 * @property int $amount
 * @property string $status
 * @property string $description 退款描述
 * @property string $failure_code
 * @property string $failure_msg
 * @property string $charge_order_id
 * @property string $transaction_no
 * @property string $funding_source 退款资金来源
 * @property array $metadata 元数据
 * @property array $extra 渠道数据
 * @property CarbonInterface $deleted_at 软删除时间
 * @property CarbonInterface $created_at 创建时间
 * @property CarbonInterface $updated_at 更新时间
 * @property CarbonInterface $time_succeed 成功时间
 *
 * @property Charge $charge
 * @property-read boolean $succeed
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Refund extends Model
{
    use SoftDelete;

    //退款状态
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';

    //退款资金来源
    const FUNDING_SOURCE_UNSETTLED = 'unsettled_funds';//使用未结算资金退款
    const FUNDING_SOURCE_RECHARGE = 'recharge_funds';//使用可用余额退款

    protected $name = 'transaction_refunds';

    /**
     * 主键值
     * @var string
     */
    protected $key = 'id';

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $type = [
        'id' => 'string',
        'amount' => 'int',
        'succeed' => 'boolean',
        'metadata' => 'array',
        'extra' => 'array'
    ];

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = 'updated_at';

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'deleted_at';

    /**
     * 新增前事件
     * @param Refund $model
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Refund $model */
        $model->id = $model->generateId();
        $model->status = static::STATUS_PENDING;
        $model->funding_source = $model->getFundingSource();
    }

    /**
     * 新增后事件
     * @param Refund $model
     */
    public static function onAfterInsert($model)
    {
        try {
            $model->send();
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 关联调用该功能模型
     * @return MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 关联用户模型
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('transaction.user'), 'user_id');
    }

    /**
     * 关联收单
     * @return BelongsTo
     */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    /**
     * 生成流水号
     * @return string
     */
    public function generateId(): string
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $row = static::where($this->key, '=', $id)->exists();
        } while ($row);
        return $id;
    }

    /**
     * 获取微信退款资金来源
     * @return string
     */
    public function getFundingSource(): string
    {
        return config('transaction.wechat.unsettled_funds', 'REFUND_SOURCE_RECHARGE_FUNDS');
    }

    /**
     * 退款是否成功
     * @return bool
     */
    public function getSucceedAttr(): bool
    {
        return $this->status == self::STATUS_SUCCEEDED;
    }

    /**
     * 设置退款错误
     * @param string $code
     * @param string $msg
     * @return bool
     */
    public function setFailure(string $code, string $msg): bool
    {
        $succeed = $this->save(['status' => self::STATUS_FAILED, 'failure_code' => $code, 'failure_msg' => $msg]);
        $this->charge->save(['amount_refunded' => $this->charge->amount_refunded - $this->amount]);//可退款金额，减回去
        Event::trigger(new RefundFailure($this));
        return $succeed;
    }

    /**
     * 设置退款成功
     * @param string $transactionNo
     * @param array $params
     * @return bool
     */
    public function setRefunded(string $transactionNo, array $params = []): bool
    {
        if ($this->succeed) {
            return true;
        }
        $this->save(['status' => self::STATUS_SUCCEEDED, 'transaction_no' => $transactionNo, 'time_succeed' => $this->freshTimestamp(), 'extra' => $params]);
        Event::trigger(new RefundSuccess($this));
        return $this->succeed;
    }

    /**
     * 网关退款
     * @return Refund
     * @throws Exception
     */
    public function send(): Refund
    {
        $this->charge->save(['refunded' => true, 'amount_refunded' => $this->charge->amount_refunded + $this->amount]);
        $channel = Transaction::getChannel($this->charge->channel);
        if ($this->charge->channel == Transaction::CHANNEL_WECHAT) {
            $refundAccount = 'REFUND_SOURCE_RECHARGE_FUNDS';
            if ($this->funding_source == Refund::FUNDING_SOURCE_UNSETTLED) {
                $refundAccount = 'REFUND_SOURCE_UNSETTLED_FUNDS';
            }
            $order = [
                'out_refund_no' => $this->id,
                'out_trade_no' => $this->charge->id,
                'total_fee' => $this->charge->amount,
                'refund_fee' => $this->amount,
                'refund_fee_type' => $this->charge->currency,
                'refund_desc' => $this->description,
                'refund_account' => $refundAccount,
                'notify_url' => url('transaction.notify.refund', ['channel' => Transaction::CHANNEL_WECHAT]),
            ];
            try {
                $response = $channel->refund($order);
                $this->setRefunded($response->transaction_id, $response->toArray());
            } catch (Exception $exception) {//设置失败
                $this->setFailure('FAIL', $exception->getMessage());
            }
        } else if ($this->charge->channel == Transaction::CHANNEL_ALIPAY) {
            $order = [
                'out_trade_no' => $this->charge->id,
                'trade_no' => $this->charge->transaction_no,
                'refund_currency' => $this->charge->currency,
                'refund_amount' => $this->amount / 100,
                'refund_reason' => '退款',
                'out_request_no' => $this->id
            ];
            try {
                $response = $channel->refund($order);
                $this->setRefunded($response->trade_no, $response->toArray());
            } catch (Exception $exception) {//设置失败
                $this->setFailure('FAIL', $exception->getMessage());
            }
        }
        return $this;
    }
}