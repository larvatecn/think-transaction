<?php

use Yansongda\Pay\Pay;

return [
    'alipay' => [
        'default' => [
            // 必填-支付宝分配的 app_id
            'app_id' => '2016082000295641',
            // 必填-应用私钥
            'app_secret_cert' => 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCDRjOg5DnX+8L+rB8d2MbrQ30Z7JPM4hiDhawHSwQCQ7RlmQNpl6b/N6IrPLcPFC1uii179U5Il5xTZynfjkUyJjnHusqnmHskftLJDKkmGbSUFMAlOv+NlpUWMJ2A+VUopl+9FLyqcV+XgbaWizxU3LsTtt64v89iZ2iC16H6/6a3YcP+hDZUjiNGQx9cuwi9eJyykvcwhDkFPxeBxHbfwppsul+DYUyTCcl0Ltbga/mUechk5BksW6yPPwprYHQBXyM16Jc3q5HbNxh3660FyvUBFLuVWIBs6RtR2gZCa6b8rOtCkPQKhUKvzRMlgheOowXsWdk99GjxGQDK5W4XAgMBAAECggEAYPKnjlr+nRPBnnNfR5ugzH67FToyrU0M7ZT6xygPfdyijaXDb2ggXLupeGUOjIRKSSijDrjLZ7EQMkguFHvtfmvcoDTDFaL2zq0a3oALK6gwRGxOuzAnK1naINkmeOmqiqrUab+21emEv098mRGbLNEXGCgltCtz7SiRdo/pgIPZ1wHj4MH0b0K2bFG3xwr51EyaLXKYH4j6w9YAXXsTdvzcJ+eRE0Yq4uGPfkziqg8d0xXSEt90HmCGHKo4O2eh1w1IlBcHfK0F6vkeUAtrtAV01MU2bNoRU147vKFxjDOVBlY1nIZY/drsbiPMuAfSsodL0hJxGSYivbKTX4CWgQKBgQDd0MkF5AIPPdFC+fhWdNclePRw4gUkBwPTIUljMP4o+MhJNrHp0sEy0sr1mzYsOT4J20hsbw/qTnMKGdgy784bySf6/CC7lv2hHp0wyS3Es0DRJuN+aTyyONOKGvQqd8gvuQtuYJy+hkIoHygjvC3TKndX1v66f9vCr/7TS0QPywKBgQCXgVHERHP+CarSAEDG6bzI878/5yqyJVlUeVMG5OXdlwCl0GAAl4mDvfqweUawSVFE7qiSqy3Eaok8KHkYcoRlQmAefHg/C8t2PNFfNrANDdDB99f7UhqhXTdBA6DPyW02eKIaBcXjZ7jEXZzA41a/zxZydKgHvz4pUq1BdbU5ZQKBgHyqGCDgaavpQVAUL1df6X8dALzkuqDp9GNXxOgjo+ShFefX/pv8oCqRQBJTflnSfiSKAqU2skosdwlJRzIxhrQlFPxBcaAcl0VTcGL33mo7mIU0Bw2H1d4QhAuNZIbttSvlIyCQ2edWi54DDMswusyAhHxwz88/huJfiad1GLaLAoGASIweMVNuD5lleMWyPw2x3rAJRnpVUZTc37xw6340LBWgs8XCEsZ9jN4t6s9H8CZLiiyWABWEBufU6z+eLPy5NRvBlxeXJOlq9iVNRMCVMMsKybb6b1fzdI2EZdds69LSPyEozjkxdyE1sqH468xwv8xUPV5rD7qd83+pgwzwSJkCgYBrRV0OZmicfVJ7RqbWyneBG03r7ziA0WTcLdRWDnOujQ9orhrkm+EY2evhLEkkF6TOYv4QFBGSHfGJ0SwD7ghbCQC/8oBvNvuQiPWI8B+00LwyxXNrkFOxy7UfIUdUmLoLc1s/VdBHku+JEd0YmEY+p4sjmcRnlu4AlzLxkWUTTg==',
            // 必填-应用公钥证书 路径
            'app_public_cert_path' => '/Users/yansongda/pay/cert/appCertPublicKey_2016082000295641.crt',
            // 必填-支付宝公钥证书 路径
            'alipay_public_cert_path' => '/Users/yansongda/pay/cert/alipayCertPublicKey_RSA2.crt',
            // 必填-支付宝根证书 路径
            'alipay_root_cert_path' => '/Users/yansongda/pay/cert/alipayRootCert.crt',
            'return_url' => 'https://yansongda.cn/alipay/return',
            'notify_url' => 'https://yansongda.cn/alipay/notify',
            // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
            'service_provider_id' => '',
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ]
    ],
    'wechat' => [
        'default' => [
            // 必填-商户号，服务商模式下为服务商商户号
            'mch_id' => '',

            // 必填-商户秘钥
            'mch_secret_key' => '',

            // 商户公钥证书路径
            'mch_public_cert_path' => config_path('wxcert/apiclient_cert.pem'),
            // 商户私钥证书路径
            'mch_secret_cert' => config_path('wxcert/apiclient_key.pem'),

            // 必填
            'notify_url' => 'https://yansongda.cn/wechat/notify',
            // 选填-公众号 的 app_id
            'mp_app_id' => '2016082000291234',
            // 选填-小程序 的 app_id
            'mini_app_id' => '',
            // 选填-app 的 app_id
            'app_id' => '',

            // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
            'wechat_public_cert_path' => [
                '45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__ . '/Cert/wechatPublicKey.crt',
            ],
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ]
    ],
    'logger' => [
        'enable' => false,
        'file' => runtime_path('log/alipay.log'),
        'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
        'type' => 'daily', // optional, 可选 daily.
        'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
    ],
    'http' => [ // optional
        'timeout' => 5.0,
        'connect_timeout' => 5.0,
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],
];
