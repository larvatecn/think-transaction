<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Transaction\Models;

use Carbon\Carbon;
use Larva\Transaction\Events\ChargeFailed;
use Larva\Transaction\Events\ChargeSucceeded;
use Larva\Transaction\Transaction;
use Larva\Transaction\TransactionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use think\facade\Event;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\HasMany;
use think\model\relation\MorphTo;
use Yansongda\Supports\Collection;

/**
 * 支付模型
 * @property int $id 收款流水号
 * @property string $trade_channel 支付渠道
 * @property string $trade_type 支付类型
 * @property string $transaction_no 支付网关交易号
 * @property string $source_id 订单ID
 * @property string $source_type 订单类型
 * @property string $subject 支付标题
 * @property string $description 描述
 * @property int $total_amount 支付金额，单位分
 * @property int $refunded_amount 已退款钱数
 * @property string $currency 支付币种
 * @property string $state 交易状态
 * @property string $client_ip 客户端IP
 * @property array $metadata 元信息
 * @property array $credential 客户端支付凭证
 * @property array $failure 错误信息
 * @property array $extra 网关返回的信息
 * @property string|null $succeed_at 支付完成时间
 * @property string|null $expired_at 过期时间
 * @property string $created_at 创建时间
 * @property string|null $updated_at 更新时间
 * @property string|null $deleted_at 软删除时间
 *
 * @property Model $source 触发该收款的订单模型
 * @property Refund $refunds 退款列表
 *
 * @property-read bool $paid 是否已付款
 * @property-read bool $refunded 是否有退款
 * @property-read bool $reversed 是否已撤销
 * @property-read bool $closed 是否已关闭
 * @property-read string $stateDesc 状态描述
 * @property-read string $outTradeNo
 * @property-read int $refundableAmount 可退款金额
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Charge extends Model
{
    use SoftDelete, Traits\UsingDatetimeAsPrimaryKey, Traits\DateTimeFormatter;

    public const STATE_SUCCESS = 'SUCCESS';
    public const STATE_REFUND = 'REFUND';
    public const STATE_NOTPAY = 'NOTPAY';
    public const STATE_CLOSED = 'CLOSED';
    public const STATE_REVOKED = 'REVOKED';
    public const STATE_USERPAYING = 'USERPAYING';
    public const STATE_PAYERROR = 'PAYERROR';
    public const STATE_ACCEPT = 'ACCEPT';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'transaction_charges';

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
        'id' => 'int',
        'trade_channel' => 'string',
        'trade_type' => 'string',
        'transaction_no' => 'string',
        'subject' => 'string',
        'description' => 'string',
        'total_amount' => 'int',
        'refunded_amount' => 'int',
        'currency' => 'string',
        'state' => 'string',
        'client_ip' => 'string',
        'metadata' => 'array',
        'extra' => 'array',
        'credential' => 'array',
        'failure' => 'array',
        'expired_at' => 'datetime',
        'succeed_at' => 'datetime',
    ];

    /**
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $stateMaps = [
        self::STATE_SUCCESS => '支付成功',
        self::STATE_REFUND => '转入退款',
        self::STATE_NOTPAY => '未支付',
        self::STATE_CLOSED => '已关闭',
        self::STATE_REVOKED => '已撤销',//已撤销（仅付款码支付会返回）
        self::STATE_USERPAYING => '用户支付中',//用户支付中（仅付款码支付会返回）
        self::STATE_PAYERROR => '支付失败',//支付失败（仅付款码支付会返回）
        self::STATE_ACCEPT => '已接收，等待扣款',
    ];

    /**
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $stateDots = [
        self::STATE_SUCCESS => 'success',
        self::STATE_REFUND => 'warning',
        self::STATE_NOTPAY => 'info',
        self::STATE_CLOSED => 'info',
        self::STATE_REVOKED => 'info',//已撤销（仅付款码支付会返回）
        self::STATE_USERPAYING => 'info',//用户支付中（仅付款码支付会返回）
        self::STATE_PAYERROR => 'error',//支付失败（仅付款码支付会返回）
        self::STATE_ACCEPT => 'warning',
    ];

    /**
     * 新增前事件
     * @param Charge $model
     * @return void
     */
    public static function onBeforeInsert(Charge $model)
    {
        $model->id = $model->generateKey();
        $model->currency = $model->currency ?: 'CNY';
        $model->expired_at = $model->expired_at ?? Carbon::now()->addHours(1)->format('Y-m-d H:i:s.u');
        $model->state = static::STATE_NOTPAY;
    }

    /**
     * 写入后事件
     * @param Charge $model
     */
    public static function onAfterInsert(Charge $model): void
    {
        if (!empty($model->trade_channel) && !empty($model->trade_type)) {//不为空就预下单
            $model->prePay();
        }
    }

    /**
     * 获取 State Label
     * @return string[]
     */
    public static function getStateMaps(): array
    {
        return static::$stateMaps;
    }

    /**
     * 获取状态Dot
     * @return string[]
     */
    public static function getStateDots(): array
    {
        return static::$stateDots;
    }

    /**
     * 关联订单
     * @return MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
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
     * 是否已经付款
     * @return bool
     */
    public function getPaidAttr(): bool
    {
        return $this->state == static::STATE_SUCCESS || $this->state == static::STATE_REFUND;
    }

    /**
     * 是否有退款
     * @return bool
     */
    public function getRefundedAttr(): bool
    {
        return $this->state == static::STATE_REFUND;
    }

    /**
     * 是否已撤销
     * @return bool
     */
    public function getReversedAttr(): bool
    {
        return $this->state == static::STATE_REVOKED;
    }

    /**
     * 是否已关闭
     * @return bool
     */
    public function getClosedAttr(): bool
    {
        return $this->state == static::STATE_CLOSED;
    }

    /**
     * 状态描述
     * @return string
     */
    public function getStateDescAttr(): string
    {
        return static::$stateMaps[$this->state] ?? '未知状态';
    }

    /**
     * 获取可退款钱数
     * @return int
     */
    public function getRefundableAmountAttr(): int
    {
        $refundableAmount = $this->total_amount - $this->refunded_amount;
        if ($refundableAmount > 0) {
            return $refundableAmount;
        }
        return 0;
    }

    /**
     * 获取OutTradeNo
     * @return string
     */
    public function getOutTradeNoAttr(): string
    {
        return (string)$this->id;
    }

    /**
     * 设置已付款状态
     * @param string $transactionNo 支付渠道返回的交易流水号。
     * @param array $extra
     * @return bool
     */
    public function markSucceeded(string $transactionNo, array $extra = []): bool
    {
        if ($this->paid) {
            return true;
        }
        $state = $this->save([
            'transaction_no' => $transactionNo,
            'expired_at' => null,
            'succeed_at' => $this->freshTimestamp(),
            'state' => static::STATE_SUCCESS,
            'credential' => [],
            'extra' => $extra
        ]);
        Event::trigger(new ChargeSucceeded($this));
        return $state;
    }

    /**
     * 设置支付错误
     * @param string $code
     * @param string $desc
     * @param array $extra
     * @return bool
     */
    public function markFailed(string $code, string $desc, array $extra = []): bool
    {
        $state = $this->save([
            'state' => static::STATE_PAYERROR,
            'credential' => [],
            'failure' => ['code' => $code, 'desc' => $desc],
            'extra' => $extra
        ]);
        Event::trigger(new ChargeFailed($this));
        return $state;
    }

    /**
     * 发起退款
     * @param string $reason 退款原因
     * @return Refund
     * @throws TransactionException
     */
    public function refund(string $reason): Refund
    {
        if (!$this->paid) {
            throw new TransactionException('Not paid, no refund.');
        } elseif ($this->refundableAmount == 0) {
            throw new TransactionException('No refundable amount.');
        } else {
            /** @var Refund $refund */
            $refund = $this->refunds()->save([
                'charge_id' => $this->id,
                'amount' => $this->total_amount,
                'reason' => $reason,
            ]);
            return $refund;
        }
    }

    /**
     * 获取指定渠道的支付凭证
     * @param string $channel 渠道
     * @param string $type 通道类型
     * @param array $metadata 元数据
     * @return array
     */
    public function getCredential(string $channel, string $type, array $metadata = []): array
    {
        $this->save(['trade_channel' => $channel, 'trade_type' => $type, 'metadata' => $metadata]);
        $this->prePay();
        $this->refresh();
        return $this->credential;
    }

    /**
     * 订单付款预下单
     */
    public function prePay()
    {
        $channel = Transaction::getChannel($this->trade_channel);
        $order = [
            'out_trade_no' => $this->outTradeNo,
        ];
        if ($this->trade_channel == Transaction::CHANNEL_WECHAT) {
            $order['spbill_create_ip'] = $this->client_ip;
            $order['total_fee'] = $this->total_amount;//总金额，单位分
            $order['body'] = $this->description ?? $this->subject;
            if ($this->expired_at) {
                $order['time_expire'] = Carbon::create($this->expired_at)->format('YmdHis');
            }
            if (isset($this->metadata['openid'])) {
                $order['openid'] = $this->metadata['openid'];
            }
            $order['notify_url'] = url('transaction.notify.wechat')->domain(true)->build();

        } elseif ($this->trade_channel == Transaction::CHANNEL_ALIPAY) {
            $order['total_amount'] = $this->total_amount / 100;//总钱数，单位元
            $order['subject'] = $this->subject;
            $order['body'] = $this->description ?? $this->subject;
            if ($this->expired_at) {
                $order['time_expire'] = $this->expired_at;
            }
            $order['notify_url'] = url('transaction.notify.alipay');
            if ($this->trade_type == 'wap') {
                $order['return_url'] = url('transaction.callback.charge', ['channel' => Transaction::CHANNEL_ALIPAY]);
            }
        }
        // 获取支付凭证
        $credential = $channel->pay($this->trade_type, $order);
        if ($credential instanceof Collection) {
            $credential = $credential->toArray();
        } elseif ($credential instanceof RedirectResponse) {
            $credential = ['url' => $credential->getTargetUrl()];
        } elseif ($credential instanceof JsonResponse) {
            $credential = json_decode($credential->getContent(), true);
        } elseif ($credential instanceof Response) {//此处判断一定要在最后
            if ($this->trade_channel == Transaction::CHANNEL_ALIPAY && $this->trade_type == 'app') {
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