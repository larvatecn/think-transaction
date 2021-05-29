<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Transaction;

use Larva\Transaction\Http\Controllers\NotifyController;
use Larva\Transaction\Http\Controllers\PaymentController;
use think\facade\Event;
use think\facade\Route;

/**
 * Class TransactionService
 * @author Tongle Xu <xutongle@gmail.com>
 */
class TransactionService extends \think\Service
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot(): void
    {
        $this->registerRoutes(function (Route $route) {
            $route->group('transaction', function (Route $route) {
                //支付回调
                $route->rule('notify/charge/:channel',PaymentController::class . "@paymentCallback", 'GET|POST')->name('transaction.callback.charge');
                //支付回调(一般用于扫码付)
                $route->get('callback/charge/:id', PaymentController::class . "@paymentSuccess")->name('transaction.success.charge');
                //支付状态查询
                $route->get('charge/:id', PaymentController::class . "@query")->name('transaction.query.charge');
                //支付回调
                $route->rule('notify/charge/:channel', NotifyController::class . "@charge", 'GET|POST')->name('transaction.notify.charge');
                //退款通知
                $route->rule('notify/refund/:channel', NotifyController::class . "@refund", 'GET|POST')->name('transaction.notify.refund');
            })->pattern(['id' => '\d+', 'channel' => '\w+']);
        });
    }
}