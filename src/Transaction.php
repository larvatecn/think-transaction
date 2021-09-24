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

use Larva\Transaction\Models\Charge;
use Larva\Transaction\Models\Refund;
use Larva\Transaction\Models\Transfer;
use think\Facade;
use Yansongda\Pay\Provider\Alipay;
use Yansongda\Pay\Provider\Wechat;

class Transaction extends Facade
{
    //支持的交易通道
    public const CHANNEL_WECHAT = 'wechat';
    public const CHANNEL_ALIPAY = 'alipay';

    //交易类型
    public const TRADE_TYPE_WEB = 'web';
    public const TRADE_TYPE_WAP = 'wap';
    public const TRADE_TYPE_APP = 'app';
    public const TRADE_TYPE_POS = 'pos';
    public const TRADE_TYPE_SCAN = 'scan';
    public const TRADE_TYPE_MINI = 'mini';

    /**
     * 获取当前Facade对应类名
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return 'transaction.alipay';
    }

    /**
     * Return the facade accessor.
     *
     * @return Alipay
     */
    public static function alipay(): Alipay
    {
        return app('transaction.alipay');
    }

    /**
     * Return the facade accessor.
     *
     * @return Wechat
     */
    public static function wechat(): Wechat
    {
        return app('transaction.wechat');
    }

    /**
     * 支持的网关
     *
     * @return string[]
     */
    public static function getChannelMaps(): array
    {
        return [
            static::CHANNEL_WECHAT => '微信',
            static::CHANNEL_ALIPAY => '支付宝',
        ];
    }

    /**
     * 支付类型
     *
     * @return string[]
     */
    public static function getTradeTypeMaps(): array
    {
        return [
            static::TRADE_TYPE_WEB => '电脑支付',
            static::TRADE_TYPE_WAP => '手机网站支付',
            static::TRADE_TYPE_APP => 'APP 支付',
            static::TRADE_TYPE_POS => '刷卡支付',
            static::TRADE_TYPE_SCAN => '扫码支付',
            static::TRADE_TYPE_MINI => '小程序支付',
        ];
    }

    /**
     * 获取支持的交易网关
     * @param string $channel
     * @return Alipay|Wechat
     * @throws \Exception
     */
    public static function getChannel(string $channel)
    {
        if ($channel == static::CHANNEL_WECHAT) {
            return static::wechat();
        } elseif ($channel == static::CHANNEL_ALIPAY) {
            return static::alipay();
        } else {
            throw new \Exception('The channel does not exist.');
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
