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
namespace Larva\Transaction\Http\Controllers;

use Larva\Transaction\Models\Charge;
use Larva\Transaction\Transaction;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

class PaymentController
{
    /**
     * 付款回调
     * @param string $channel
     * @return \think\response\Redirect|\think\response\View|void
     */
    public function paymentCallback(string $channel)
    {
        try {
            $pay = Transaction::getChannel($channel);
            $params = $pay->verify(); // 验签
            $charge = null;
            if ($channel == Transaction::CHANNEL_ALIPAY) {
                if (isset($params['trade_status']) && ($params['trade_status'] == 'TRADE_SUCCESS' || $params['trade_status'] == 'TRADE_FINISHED')) {
                    $charge = Transaction::getCharge($params['out_trade_no']);
                    $charge->markSucceeded($params['trade_no']);
                    if ($charge->metadata['return_url']) {
                        return redirect($charge->metadata['return_url']);
                    }
                }
            }
            return view('transaction:return', ['charge' => $charge]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 扫码付成功回调
     * @param string $id
     * @return \think\response\Redirect|\think\response\View|void
     * @throws ModelNotFoundException
     */
    public function paymentSuccess(string $id)
    {
        $charge = Transaction::getCharge($id);
        if ($charge && $charge->paid) {
            if ($charge->metadata['return_url']) {
                return redirect($charge->metadata['return_url']);
            }
            return view('transaction:return', ['charge' => $charge]);
        }
        throw new ModelNotFoundException('该交易不存在！', Charge::class);
    }

    /**
     * 查询交易状态
     * @param string $id
     * @return array
     * @throws ModelNotFoundException
     */
    public function query(string $id): array
    {
        $charge = Transaction::getCharge($id);
        if ($charge) {
            return $charge->toArray();
        }
        throw new ModelNotFoundException('该交易不存在！', Charge::class);
    }
}
