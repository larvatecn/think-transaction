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
namespace Larva\Transaction;

use Larva\Transaction\Http\Controllers\NotifyController;
use Larva\Transaction\Http\Controllers\PaymentController;
use think\Route;
use Yansongda\Pay\Pay;

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
        Pay::config(config('transaction'));

        $this->app->bind('transaction.alipay', function () {
            return Pay::alipay();
        });

        $this->app->bind('transaction.wechat', function () {
            return Pay::wechat();
        });
    }

    public function boot(): void
    {
        $this->registerRoutes(function (Route $route) {
            $route->group('transaction', function (Route $route) {
                //支付通知
                $route->rule('notify/wechat', NotifyController::class . '@wechat', 'GET|POST')->name('transaction.notify.wechat');
                $route->rule('notify/alipay', NotifyController::class . '@alipay', 'GET|POST')->name('transaction.notify.alipay');
                //退款通知
                $route->rule('notify/refund/:channel', NotifyController::class . '@refund', 'GET|POST')->name('transaction.notify.refund');
                //支付回调
                $route->rule('notify/charge/:channel', PaymentController::class . '@paymentCallback', 'GET|POST')->name('transaction.notify.charge');
                //支付回调(一般用于扫码付)
                $route->get('callback/charge/:id', PaymentController::class . '@paymentSuccess')->name('transaction.success.charge');
                //支付状态查询
                $route->get('charge/:id', PaymentController::class . '@query')->name('transaction.query.charge');
            });
        });
    }
}
