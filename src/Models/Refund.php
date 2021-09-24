<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Transaction\Models;

use Carbon\CarbonInterface;
use Exception;
use Larva\Transaction\Events\RefundFailed;
use Larva\Transaction\Events\RefundSucceeded;
use Larva\Transaction\Transaction;
use think\facade\Event;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;

/**
 * 退款处理模型
 * @property int $id 退款流水号
 * @property int $charge_id 付款ID
 * @property string $transaction_no 网关流水号
 * @property int $amount 退款金额/单位分
 * @property string $reason 退款描述
 * @property string $status 退款状态
 * @property array $failure 退款失败对象
 * @property array $extra 渠道返回的额外信息
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string|null $succeed_at 成功时间
 * @property string|null $deleted_at 删除时间
 *
 * @property Charge $charge
 * @property-read boolean $succeed
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Refund extends Model
{
    use SoftDelete, Traits\UsingDatetimeAsPrimaryKey, Traits\DateTimeFormatter;

    // 退款状态机
    public const STATUS_PENDING = 'PENDING';//待处理
    public const STATUS_SUCCESS = 'SUCCESS';//退款成功
    public const STATUS_CLOSED = 'CLOSED';//退款关闭
    public const STATUS_PROCESSING = 'PROCESSING';//退款处理中
    public const STATUS_ABNORMAL = 'ABNORMAL';//退款异常

    /**
     * 模型名称
     *
     * @var string
     */
    protected $name = 'transaction_refunds';

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $type = [
        'id' => 'int',
        'charge_id' => 'int',
        'transaction_no' => 'string',
        'amount' => 'int',
        'reason' => 'string',
        'status' => 'string',
        'extra' => 'array',
        'failure' => 'array'
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
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $statusMaps = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_SUCCESS => '退款成功',
        self::STATUS_CLOSED => '退款关闭',
        self::STATUS_PROCESSING => '退款处理中',
        self::STATUS_ABNORMAL => '退款异常',
    ];

    /**
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $statusDots = [
        self::STATUS_PENDING => 'info',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_CLOSED => 'info',
        self::STATUS_PROCESSING => 'warning',
        self::STATUS_ABNORMAL => 'error',
    ];

    /**
     * 新增前事件
     * @param Refund $model
     * @return void
     */
    public static function onBeforeInsert(Refund $model)
    {
        $model->id = $model->generateKey();
        $model->status = static::STATUS_PENDING;
    }

    /**
     * 写入后
     * @param Refund $model
     * @throws Exception
     */
    public static function onAfterInsert(Refund $model): void
    {
        $model->charge->refunded_amount = $model->charge->refunded_amount + $model->amount;
        $model->charge->state = Charge::STATE_REFUND;
        $model->charge->save();
        $model->gatewayHandle();
    }

    /**
     * 获取 Status Label
     * @return string[]
     */
    public static function getStatusMaps(): array
    {
        return static::$statusMaps;
    }

    /**
     * 获取状态Dot
     * @return string[]
     */
    public static function getStateDots(): array
    {
        return static::$statusDots;
    }

    /**
     * 关联收单
     *
     * @return BelongsTo
     */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    /**
     * 退款是否成功
     * @return bool
     */
    public function getSucceedAttr(): bool
    {
        return $this->status == self::STATUS_SUCCESS;
    }

    /**
     * 设置退款错误
     * @param string|int $code
     * @param string $desc
     * @param array $extra
     * @return bool
     */
    public function markFailed($code, string $desc, array $extra = []): bool
    {
        $succeed = $this->save([
            'status' => self::STATUS_ABNORMAL,
            'failure' => ['code' => $code, 'desc' => $desc],
            'extra' => $extra
        ]);
        $this->charge->refunded_amount = $this->charge->refunded_amount - $this->amount;
        $this->charge->save();
        Event::trigger(new RefundFailed($this));
        return $succeed;
    }

    /**
     * 设置退款处理中
     * @param array $extra
     * @return bool
     */
    public function markProcessing(array $extra = []): bool
    {
        return $this->save([
            'status' => self::STATUS_PROCESSING,
            'extra' => $extra
        ]);
    }

    /**
     * 关闭退款
     * @param string|int $code
     * @param string $desc
     * @param array $extra
     * @return bool
     */
    public function markClosed($code, string $desc, array $extra = []): bool
    {
        $succeed = $this->save([
            'status' => static::STATUS_CLOSED,
            'credential' => [],
            'failure' => ['code' => $code, 'desc' => $desc],
            'extra' => $extra
        ]);
        $this->charge->refunded_amount = $this->charge->refunded_amount - $this->amount;
        $this->charge->save();
        Event::trigger(new RefundClosed($this));
        return $succeed;
    }

    /**
     * 设置退款完成
     * @param string $transactionNo
     * @param array $extra
     * @return bool
     */
    public function markSucceeded(string $transactionNo, array $extra = []): bool
    {
        if ($this->succeed) {
            return true;
        }
        $this->save([
            'status' => self::STATUS_SUCCESS,
            'transaction_no' => $transactionNo,
            'succeed_at' => $this->freshTimestamp(),
            'extra' => $extra
        ]);
        Event::trigger(new RefundSucceeded($this));
        return $this->succeed;
    }

    /**
     * 网关退款
     * @return Refund
     * @throws Exception
     */
    public function gatewayHandle(): Refund
    {
        $channel = Transaction::getChannel($this->charge->trade_channel);
        if ($this->charge->trade_channel == Transaction::CHANNEL_WECHAT) {
            $order = [
                'out_refund_no' => $this->id,
                'out_trade_no' => $this->charge->id,
                'total_fee' => $this->charge->total_amount,
                'refund_fee' => $this->amount,
                'refund_fee_type' => $this->charge->currency,
                'refund_desc' => $this->reason,
                'refund_account' => 'REFUND_SOURCE_RECHARGE_FUNDS',
                'notify_url' => url('transaction.notify.refund', ['channel' => Transaction::CHANNEL_WECHAT])->domain(true)->build(),
            ];
            try {
                $response = $channel->refund($order);
                if (isset($response->result_code) && $response->result_code == 'SUCCESS') {
                    $this->markSucceeded($response->transaction_id, $response->toArray());
                } else {
                    $this->markFailed($response->result_code, $response->return_msg, $response->toArray());
                }
            } catch (Exception $exception) {//设置失败
                $this->markFailed('FAIL', $exception->getMessage());
            }
        } elseif ($this->charge->trade_channel == Transaction::CHANNEL_ALIPAY) {
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
                $this->markSucceeded($response->trade_no, $response->toArray());
            } catch (Exception $exception) {//设置失败
                $this->markFailed('FAIL', $exception->getMessage());
            }
        }
        return $this;
    }
}
