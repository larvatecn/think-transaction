<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Transaction\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Larva\Transaction\Events\TransferFailure;
use Larva\Transaction\Events\TransferShipped;
use Larva\Transaction\Transaction;
use think\facade\Event;
use think\facade\Log;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;
use think\model\relation\MorphTo;

/**
 * 企业付款模型，处理提现
 *
 * @property string $id 付款单ID
 * @property string $channel 付款渠道
 * @property-read boolean $paid 是否已经转账
 * @property string $status 状态
 * @property mixed $source 触发源对象
 * @property int $amount 金额
 * @property string $currency 币种
 * @property string $recipient_id 接收者ID
 * @property string $description 描述
 * @property string $transaction_no 网关交易号
 * @property string $failure_msg 失败详情
 * @property array $metadata 元数据
 * @property array $extra 扩展数据
 * @property CarbonInterface $transferred_at 交易成功时间
 * @property CarbonInterface $deleted_at 软删除时间
 * @property CarbonInterface $created_at 创建时间
 * @property CarbonInterface $updated_at 更新时间
 * @property-read boolean $scheduled
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Transfer extends Model
{
    use SoftDelete;

    //付款状态
    const STATUS_SCHEDULED = 'scheduled';//scheduled: 待发送
    const STATUS_PENDING = 'pending';//pending: 处理中
    const STATUS_PAID = 'paid';//paid: 付款成功
    const STATUS_FAILED = 'failed';//failed: 付款失败

    protected $name = 'transaction_transfer';

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
     * @param Transfer $model
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Transfer $model */
        $model->id = $model->generateId();
        $model->currency = $model->currency ?: 'CNY';
        $model->status = static::STATUS_SCHEDULED;
    }

    /**
     * 新增后事件
     * @param Transfer $model
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
     * 查询已付款的
     * @param  $query
     * @return mixed
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * 生成流水号
     * @return string
     */
    protected function generateId(): string
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $row = static::where($this->key, $id)->find();
        } while ($row);
        return $id;
    }

    /**
     * 是否已付款
     * @return boolean
     */
    public function getPaidAttr(): bool
    {
        return $this->status == static::STATUS_PAID;
    }

    /**
     * 是否待发送
     * @return boolean
     */
    public function getScheduledAttr(): bool
    {
        return $this->status == static::STATUS_SCHEDULED;
    }

    /**
     * 设置已付款
     * @param string $transactionNo
     * @param array $params
     * @return bool
     */
    public function setPaid(string $transactionNo, array $params = []): bool
    {
        if ($this->paid) {
            return true;
        }
        $paid = $this->save(['transaction_no' => $transactionNo, 'transferred_at' => Carbon::now(), 'status' => static::STATUS_PAID, 'extra' => $params]);
        Event::trigger(new TransferShipped($this));
        return $paid;
    }

    /**
     * 设置提现错误
     * @param string $code
     * @param string $msg
     * @return bool
     */
    public function setFailure(string $code, string $msg): bool
    {
        $res = $this->save(['status' => self::STATUS_FAILED, 'failure_code' => $code, 'failure_msg' => $msg]);
        Event::trigger(new TransferFailure($this));
        return $res;
    }

    /**
     * 主动发送付款请求到网关
     * @return Transfer
     * @throws Exception
     */
    public function send(): Transfer
    {
        if ($this->status == static::STATUS_SCHEDULED) {
            $channel = Transaction::getChannel($this->channel);
            if ($this->channel == Transaction::CHANNEL_WECHAT) {
                $config = [
                    'partner_trade_no' => $this->id,
                    'openid' => $this->recipient_id,
                    'check_name' => 'NO_CHECK',
                    'amount' => $this->amount,
                    'desc' => $this->description,
                    'type' => $this->extra['type'],
                ];
                if (isset($this->extra['user_name'])) {
                    $config['check_name'] = 'FORCE_CHECK';
                    $config['re_user_name'] = $this->extra['user_name'];
                }
                try {
                    $response = $channel->transfer($config);
                    $this->setPaid($response->payment_no, $response->toArray());
                } catch (Exception $exception) {//设置付款失败
                    $this->setFailure('FAIL', $exception->getMessage());
                }
            } else if ($this->channel == Transaction::CHANNEL_ALIPAY) {
                $config = [
                    'out_biz_no' => $this->id,
                    'payee_type' => $this->extra['recipient_account_type'],
                    'payee_account' => $this->recipient_id,
                    'amount' => $this->amount / 100,
                    'remark' => $this->description,
                ];
                if (isset($this->extra['recipient_name'])) {
                    $config['payee_real_name'] = $this->extra['recipient_name'];
                }
                try {
                    $response = $channel->transfer($config);
                    $this->setPaid($response->payment_no, $response->toArray());
                } catch (Exception $exception) {//设置提现失败
                    $this->setFailure('FAIL', $exception->getMessage());
                }
            }
        }
        return $this;
    }
}