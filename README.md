# think-transaction

[![Latest Stable Version](https://poser.pugx.org/larva/think-transaction/v/stable.png)](https://packagist.org/packages/larva/think-transaction)
[![Total Downloads](https://poser.pugx.org/larva/think-transaction/downloads.png)](https://packagist.org/packages/larva/think-transaction)


这是一个内部收单系统，依赖 yansongda/pay 这个组件，本收单系统，统一了调用。
备注，交易单位是分；

## 环境需求

- PHP >= 7.3.0

## Installation

```bash
composer require larva/think-transaction -vv
```

事件
```php
\Larva\Transaction\Events\ChargeClosed 交易已关闭
\Larva\Transaction\Events\ChargeFailure 交易失败
\Larva\Transaction\Events\ChargeShipped 交易已支付
\Larva\Transaction\Events\RefundFailure 退款失败事件
\Larva\Transaction\Events\RefundSuccess 退款成功事件
\Larva\Transaction\Events\TransferFailure 企业付款失败事件
\Larva\Transaction\Events\TransferShipped 企业付款成功事件
```

你自己的订单关联

```php
/**
 * @property Charge $change
 */
class Order extends Model {

    /**
     * Get the entity's charge.
     *
     * @return MorphOne
     */
    public function charge()
    {
        return $this->morphOne(Charge::class, 'order');
    }

    /**
     * 设置交易成功
     */
    public function setSucceeded()
    {
        $this->update(['pay_channel' => $this->charge->channel, 'status' => static::STATUS_PAY_SUCCEEDED, 'pay_succeeded_at' => $this->freshTimestamp()]);
    }

    /**
     * 设置交易失败
     */
    public function setFailure()
    {
        $this->update(['status' => static::STATUS_FAILED]);
    }

    /**
     * 发起退款
     * @param string $description 退款描述
     * @return Model|Refund
     * @throws Exception
     */
    public function setRefund(string $description)
    {
        if ($this->paid && $this->charge->allowRefund) {
            $refund = $this->charge->refunds()->create([
                'user_id' => $this->user_id,
                'amount' => $this->amount,
                'description' => $description,
                'charge_id' => $this->charge->id,
                'charge_order_id' => $this->id,
            ]);
            $this->update(['refunded' => true]);
            return $refund;
        }
        throw new Exception ('Not paid, no refund.');
    }
}
```