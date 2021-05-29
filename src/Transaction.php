<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types = 1);

namespace Larva\Transaction;

use Larva\Transaction\Models\Charge;
use Larva\Transaction\Models\Refund;
use Larva\Transaction\Models\Transfer;
use Yansongda\Pay\Pay;

class Transaction
{
    //支持的交易通道
    const CHANNEL_WECHAT = 'wechat';
    const CHANNEL_ALIPAY = 'alipay';

    /**
     * 获取支持的交易网关
     * @param string $channel
     * @return \Yansongda\Pay\Gateways\Alipay|\Yansongda\Pay\Gateways\Wechat
     * @throws \Exception
     */
    public static function getChannel(string $channel)
    {
        if ($channel == static::CHANNEL_WECHAT) {
            return Pay::wechat(config('transaction.wechat'));
        } else if ($channel == static::CHANNEL_ALIPAY) {
            return Pay::alipay(config('transaction.alipay'));
        } else {
            throw new \Exception ('The channel does not exist.');
        }
    }

    /**
     * 获取付款单
     * @param string $id
     * @return array|Charge|null
     */
    public static function getCharge(string $id)
    {
        return Charge::find($id);
    }

    /**
     * 获取退款单
     * @param string $id
     * @return array|Refund|null
     */
    public static function getRefund(string $id)
    {
        return Refund::find($id);
    }

    /**
     * 获取企业付款
     * @param string $id
     * @return array|Transfer|null
     */
    public static function getTransfer(string $id)
    {
        return Transfer::find($id);
    }
}