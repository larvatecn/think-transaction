<?php
declare(strict_types=1);

use Yansongda\Pay\Pay;

return [
    'alipay' => [
        'default' => [
            // 支付宝分配的 app_id
            'app_id' => '',
            // 应用私钥
            'app_secret_cert' => config_path('alicert/xxx.pem'),
            // 应用公钥证书 路径
            'app_public_cert_path' => config_path('alicert/appCertPublicKey_xxx.crt'),
            // 支付宝公钥证书 路径
            'alipay_public_cert_path' => config_path('alicert/alipayCertPublicKey_RSA2.crt'),
            // 支付宝根证书 路径
            'alipay_root_cert_path' => config_path('alicret/alipayRootCert.crt'),
            'return_url' => '',//不用配置
            'notify_url' => '',//不用配置
            'mode' => Pay::MODE_NORMAL,
        ],
    ],
    'wechat' => [
        'default' => [
            // 公众号 的 app_id
            'mp_app_id' => '',

            // 小程序 的 app_id
            'mini_app_id' => '',

            // app 的 app_id
            'app_id' => '',

            // 商户号
            'mch_id' => '',

            // 商户秘钥 V3
            'mch_secret_key' => '',

            // 商户公钥证书路径
            'mch_public_cert_path' => config_path('wxcert/apiclient_cert.pem'),
            // 商户私钥证书路径
            'mch_secret_cert' => config_path('wxcert/apiclient_key.pem'),

            // 微信公钥证书路径
            'wechat_public_cert_path' => [
                '' => '',
            ],
            'notify_url' => '',//不用配置
            'mode' => Pay::MODE_NORMAL,
        ],
    ],
    'http' => [ // optional
        'timeout' => 5.0,
        'connect_timeout' => 5.0,
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],
    // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
    'logger' => [
        'enable' => true,
        'file' => runtime_path('log/pay.log'),
        'level' => env('LOG_LEVEL'),
        'type' => 'daily', // optional, 可选 daily.
        'max_file' => 30,
    ],
];