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
use Larva\Transaction\Events\ChargeClosed;
use Larva\Transaction\Events\ChargeFailure;
use Larva\Transaction\Events\ChargeShipped;
use Larva\Transaction\Transaction;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use think\facade\Event;
use think\facade\Log;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\model\relation\MorphTo;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Exceptions\InvalidConfigException;
use Yansongda\Pay\Exceptions\InvalidSignException;
use Yansongda\Supports\Collection;

/**
 * 支付模型
 * @property string $id
 * @property int $user_id 用户ID
 * @property boolean $reversed 已撤销
 * @property boolean $refunded 已退款
 * @property string $channel 支付渠道
 * @property string $type  支付类型
 * @property string $subject 支付标题
 * @property string $body 支付内容
 * @property string $source_id 订单ID
 * @property float $amount 支付金额，单位分
 * @property string $currency 支付币种
 * @property boolean $paid 是否支付成功
 * @property string $transaction_no 支付网关交易号
 * @property int $amount_refunded 已经退款的金额
 * @property CarbonInterface $time_expire 时间
 * @property string $client_ip 客户端IP
 * @property string $failure_code 失败代码
 * @property string $failure_msg 失败消息
 * @property string $description 描述
 * @property array $credential 客户端支付凭证
 * @property array $metadata 元数据
 * @property array $extra 渠道数据
 *
 * @property \app\model\User $user
 * @property Model $source
 * @property Refund $refunds
 *
 * @property CarbonInterface $time_paid 付款时间
 * @property CarbonInterface $deleted_at 软删除时间
 * @property CarbonInterface $created_at 创建时间
 * @property CarbonInterface $updated_at 更新时间
 *
 * @property-read int $refundable 可退款金额
 * @property-read boolean $allowRefund 是否可以退款
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Charge extends Model
{
    use SoftDelete;

    protected $name = 'transaction_charges';

    /**
     * 主键值
     * @var string
     */
    protected $key = 'id';

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
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $type = [
        'id' => 'string',
        'amount' => 'int',
        'paid' => 'boolean',
        'refunded' => 'boolean',
        'reversed' => 'boolean',
        'extra' => 'array',
        'credential' => 'array',
        'metadata' => 'array',
        'time_expire' => 'datetime',
        'time_paid' => 'datetime',
    ];

    /**
     * 新增前事件
     * @param Charge $model
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Charge $model */
        $model->id = $model->generateId();
        $model->currency = $model->currency ?: 'CNY';
    }

    /**
     * 新增后事件
     * @param Charge $model
     */
    public static function onAfterInsert($model)
    {
        if (!empty($model->channel) && !empty($model->type)) {//不为空就预下单
            $model->prePay();
        }
//        if (!empty($model->time_expire)) {//订单失效时间不为空
//            CheckChargeJob::dispatch($model)->delay(2);
//        }
    }

    /**
     * 获取指定渠道的支付凭证
     * @param string $channel
     * @param string $type
     * @return array
     */
    public function getCredential(string $channel, string $type): array
    {
        $this->save(['channel' => $channel, 'type' => $type]);
        $this->prePay();
        $this->refresh();
        return $this->credential;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePaid($query)
    {
        return $query->where('paid', true);
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
     * 关联退款
     * @return HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 是否还可以继续退款
     * @return boolean
     */
    public function getAllowRefundAttr(): bool
    {
        if ($this->paid && $this->refundable > 0) {
            return true;
        }
        return false;
    }

    /**
     * 获取可退款钱数
     * @return float|int
     */
    public function getRefundableAttr()
    {
        return $this->amount - $this->amount_refunded;
    }

    /**
     * 获取Body
     * @return string
     */
    public function getBodyAttr(): string
    {
        return $this->getOrigin('body') ?: $this->subject;
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
     * 设置支付错误
     * @param string $code
     * @param string $msg
     * @return bool
     */
    public function setFailure(string $code, string $msg): bool
    {
        $status = $this->save(['failure_code' => $code, 'failure_msg' => $msg]);
        Event::trigger(new ChargeFailure($this));
        return $status;
    }

    /**
     * 设置已付款状态
     * @param string $transactionNo 支付渠道返回的交易流水号。
     * @return bool
     */
    public function setPaid(string $transactionNo): bool
    {
        if ($this->paid) {
            return true;
        }
        $paid = $this->save(['transaction_no' => $transactionNo, 'time_paid' => $this->freshTimestamp(), 'paid' => true]);
        Event::trigger(new ChargeShipped($this));
        return $paid;
    }

    /**
     * 关闭关闭该笔收单
     * @return bool
     * @throws Exception
     */
    public function setClose(): bool
    {
        if ($this->paid) {
            $this->save(['failure_code' => 'FAIL', 'failure_msg' => '已支付，无法撤销']);
            return false;
        } else if ($this->reversed) {//已经撤销
            return true;
        } else {
            $channel = Transaction::getChannel($this->channel);
            try {
                if ($channel->close($this->id)) {
                    Event::trigger(new ChargeClosed($this));
                    $this->save(['reversed' => true, 'credential' => []]);
                    return true;
                }
                return false;
            } catch (GatewayException | InvalidArgumentException | InvalidConfigException | InvalidSignException $e) {
                Log::error($e->getMessage());
            }
        }
        return false;
    }


    /**
     * 发起退款
     * @param string $description 退款描述
     * @return Model|Refund
     * @throws Exception
     */
    public function setRefund(string $description)
    {
        if ($this->paid) {
            $refund = $this->refunds()->create([
                'user_id' => $this->user_id,
                'amount' => $this->amount,
                'description' => $description,
                'charge_id' => $this->id,
                'charge_order_id' => $this->source_id,
            ]);
            $this->save(['refunded' => true]);
            return $refund;
        }
        throw new Exception ('Not paid, no refund.');
    }


    /**
     * 订单付款预下单
     */
    public function prePay()
    {
        $channel = Transaction::getChannel($this->channel);
        $order = [
            'out_trade_no' => $this->id,
        ];

        if ($this->channel == Transaction::CHANNEL_WECHAT) {
            $order['spbill_create_ip'] = $this->client_ip;
            $order['total_fee'] = $this->amount;//总金额，单位分
            $order['body'] = $this->body;
            if ($this->time_expire) {
                $order['time_expire'] = $this->time_expire->format('YmdHis');
            }
            $order['notify_url'] = url('transaction.notify.charge', ['channel' => Transaction::CHANNEL_WECHAT]);
        } else if ($this->channel == Transaction::CHANNEL_ALIPAY) {
            $order['total_amount'] = $this->amount / 100;//总钱数，单位元
            $order['subject'] = $this->subject;
            if ($this->body) {
                $order['body'] = $this->body;
            }
            if ($this->time_expire) {
                $order['time_expire'] = $this->time_expire;
            }
            $order['notify_url'] = url('transaction.notify.charge', ['channel' => Transaction::CHANNEL_ALIPAY]);
            if ($this->type == 'wap') {
                $order['return_url'] = url('transaction.callback.charge', ['channel' => Transaction::CHANNEL_ALIPAY]);
            }
        }
        // 获取支付凭证
        $credential = $channel->pay($this->type, $order);
        if ($credential instanceof Collection) {
            $credential = $credential->toArray();
        } else if ($credential instanceof RedirectResponse) {
            $credential = ['url' => $credential->getTargetUrl()];
        } else if ($credential instanceof JsonResponse) {
            $credential = json_decode($credential->getContent(), true);
        } else if ($credential instanceof Response) {//此处判断一定要在最后
            if ($this->channel == Transaction::CHANNEL_ALIPAY && $this->type == 'app') {
                $params = [];
                parse_str($credential->getContent(), $params);
                $credential = $params;
            } else {//WEB H5
                $credential = ['html' => $credential->getContent()];
            }
        }
        $this->save(['credential' => $credential]);
    }
}