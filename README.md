# think-transaction

[![Latest Stable Version](https://poser.pugx.org/larva/think-transaction/v/stable.png)](https://packagist.org/packages/larva/think-transaction)
[![Total Downloads](https://poser.pugx.org/larva/think-transaction/downloads.png)](https://packagist.org/packages/larva/think-transaction)


这是一个内部收单系统，依赖 yansongda/pay 这个组件，本收单系统，统一了调用。
备注，交易单位是分；

## 环境需求

- PHP >= 7.4.0

## 安装

```shell
# yansongda/pay 2.x
composer require "larva/think-transaction:^2.0"

# yansongda/pay 3.x
composer require "larva/think-transaction:^3.0"
```

由于 `ThinkPHP` 不支持发布迁移文件到应用目录，所以你需要手动复制迁移文件到你应用的迁移目录后执行迁移；
迁移文件在 `vendor/larva/think-transaction/migrations` 下；

事件
```php
\Larva\Transaction\Events\ChargeClosed 交易已关闭
\Larva\Transaction\Events\ChargeFailed 交易失败
\Larva\Transaction\Events\ChargeSucceeded 交易已支付
\Larva\Transaction\Events\RefundFailed 退款失败事件
\Larva\Transaction\Events\RefundSucceeded 退款成功事件
\Larva\Transaction\Events\TransferFailed 企业付款（提现）失败事件
\Larva\Transaction\Events\TransferSucceeded 企业付款（提现）成功事件
```

你自己的订单关联参考下面的类，总体思路是你自己的订单模型或者其他需要用户付款的模型在模型创建后你可以用创建后事件来调用付款；
需要在前端或者APP端执行付款逻辑的时候，你只需要 `$model->charge->getCredential();`
就能获取到付款参数，带入对应的SDK即可，比如APP的SDK需要的几个参数，这里都能获取到。
`\Larva\Transaction\Events\ChargeSucceeded` 事件你可以监听到付款成功的事件，
你只需判断事件的source属性是否是你当前这个模型的实例即可知道是谁触发了付款并且付款成功；


```php
namespace app\model;

use Exception;
use Larva\Transaction\Models\Charge;
use Larva\Transaction\Models\Refund;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\MorphOne;

/**
 * 订单模型
 * @property int $id
 * @property int $user_id
 * @property-read boolean $paid
 * @property string $subject
 * @property string $body
 * @property string $amount
 * @property string $channel
 * @property string $type
 * @property string $client_ip
 * @property string $status
 * @property datetime $pay_succeeded_at
 *
 * @property Charge $charge
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Order extends Model
{
    use SoftDelete;

    const STATUS_PENDING = 'pending';//处理中： pending
    const STATUS_SUCCEEDED = 'succeeded';//完成： succeeded
    const STATUS_FAILED = 'failed';//失败： failed

    protected $name = 'orders';

    /**
     * 新增后会自动触发该事件，这时候就自动创建了付款参数；
     * @param Order $model
     */
    public static function onAfterInsert($model)
    {
        $model->charge()->save([
            'user_id' => $model->user_id,
            'amount' => $model->amount,//金额单位分
            'channel' => $model->channel,//付款通道 ，如 wechat
            'subject' => '订单付款',
            'body' => '订单详情',
            'client_ip' => $model->client_ip,
            'type' => $model->type,//交易类型 如 app
        ]);
    }

    /**
     * Get the entity's charge.
     *
     * @return MorphOne
     */
    public function charge(): MorphOne
    {
        return $this->morphOne(Charge::class, 'source');
    }

    /**
     * 设置交易成功
     */
    public function setSucceeded()
    {
        $this->update(['channel' => $this->charge->channel, 'status' => static::STATUS_SUCCEEDED, 'pay_succeeded_at' => $this->freshTimestamp()]);
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
     * @return Refund
     * @throws Exception
     */
    public function setRefund(string $description): Refund
    {
        if ($this->paid && $this->charge->allowRefund) {
            /** @var Refund $refund */
            $refund = $this->charge->refunds()->save([
                'user_id' => $this->user_id,
                'amount' => $this->amount,
                'description' => $description,
                'charge_id' => $this->charge->id,
                'charge_order_id' => $this->id,
            ]);
            $this->save(['refunded' => true]);
            return $refund;
        }
        throw new Exception ('Not paid, no refund.');
    }
}
```

```php
$order = Order::create([
    'user_id' => 1,
    'subject' => '标题',
    'client_ip' => '192.168.1.1',
    'amount' => 100,
    'channel' => 'wechat',
    'type' => 'app'
    //你创建订单的参数
]);
//获取付款凭证 数组
$credential = $order->charge->getCredential();
```

创建一个事件监听器 `php think make:subscribe ChargeShipped`

```php
<?php
declare (strict_types=1);

namespace app\subscribe;

use think\Event;

class TransactionCharge
{

    public function onChargeShipped($charge)
    {
        if ($charge->source instanceof Order) {
            $charge->source->setSucceeded();
        }
    }

    public function onChargeClosed($charge)
    {

    }

    public function onChargeFailure($charge)
    {

    }


    public function subscribe(Event $event)
    {
        $event->listen(\Larva\Transaction\Events\ChargeClosed::class, [$this, 'onChargeClosed']);
        $event->listen(\Larva\Transaction\Events\ChargeFailed::class, [$this, 'onChargeFailure']);
        $event->listen(\Larva\Transaction\Events\ChargeSucceeded::class, [$this, 'onChargeShipped']);
    }
}
```

在你 `app/event.php` 中加入订阅器如：

```php
<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => [

        ],
        'LogLevel' => [],
        'LogWrite' => [],
    ],

    'subscribe' => [
        'app\subscribe\TransactionCharge'
    ],
];

```