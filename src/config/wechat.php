<?php

return [
    /*
     * Debug 模式，bool 值：true/false
     *
     * 当值为 false 时，所有的日志都不会记录
     */
    'debug'  => env('APP_DEBUG', false),

    /*
     * 使用 Laravel 的缓存系统
     */
    'use_laravel_cache' => true,

    /**
     * Guzzle 配置
     */
    'guzzle' => [
        'timeout' => 30.0
    ],

    /*
     * 账号基本信息，请从微信公众平台/开放平台获取
     */
    'app_id'  => env('WECHAT_APPID', 'wxcca6c9a00c8010cb'),         // AppID
    'secret'  => env('WECHAT_SECRET', 'c7545455e25208847b41aaa25d7a9721'),     // AppSecret
    'token'   => env('WECHAT_TOKEN', 'Someline'),          // Token
    'aes_key' => env('WECHAT_AES_KEY', 'zPYj3tMwZBkpRTZzty7ISGnOazjknsJHy4WFAkpQJxl'),                    // EncodingAESKey

    /**
     * 开放平台第三方平台配置信息
     */
    //'open_platform' => [
    /**
     * 事件推送URL
     */
    //'serve_url' => env('WECHAT_OPEN_PLATFORM_SERVE_URL', 'serve'),
    //],

    /*
     * 日志配置
     *
     * level: 日志级别，可选为：
     *                 debug/info/notice/warning/error/critical/alert/emergency
     * file：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log' => [
        'level' => env('WECHAT_LOG_LEVEL', 'debug'),
        'file'  => env('WECHAT_LOG_FILE', storage_path('logs/wechat.log')),
    ],

    /*
     * OAuth 配置
     *
     * only_wechat_browser: 只在微信浏览器跳转
     * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
     * callback：OAuth授权完成后的回调页地址(如果使用中间件，则随便填写。。。)
     */
    'oauth' => [
        'only_wechat_browser' => true,
        'scopes'   => array_map('trim', explode(',', env('WECHAT_OAUTH_SCOPES', 'snsapi_userinfo'))),
        'callback' => env('WECHAT_OAUTH_CALLBACK', '/wechat/callback'),
    ],

    /*
     * 微信支付
     */
//    'payment' => [
//        'merchant_id'        => env('WECHAT_PAYMENT_MERCHANT_ID', '1407984902'),
//        'key'                => env('WECHAT_PAYMENT_KEY', '0cc969aa07394cf37166ee6c210e4bae'),
//        'cert_path'          => env('WECHAT_PAYMENT_CERT_PATH', storage_path('app/wechat_cert/apiclient_cert.pem')), // XXX: 绝对路径！！！！
//        'key_path'           => env('WECHAT_PAYMENT_KEY_PATH', storage_path('app/wechat_cert/apiclient_key.pem')),      // XXX: 绝对路径！！！！
//        'notify_url'         => '/wechat/pay/notify',       // 默认的订单回调地址，你也可以在下单时单独设置来想覆盖它
//        // 'device_info'     => env('WECHAT_PAYMENT_DEVICE_INFO', ''),
//        // 'sub_app_id'      => env('WECHAT_PAYMENT_SUB_APP_ID', ''),
//        // 'sub_merchant_id' => env('WECHAT_PAYMENT_SUB_MERCHANT_ID', ''),
//        // ...
//    ],

    /*
     * 开发模式下的免授权模拟授权用户资料
     *
     * 当 enable_mock 为 true 则会启用模拟微信授权，用于开发时使用，开发完成请删除或者改为 false 即可
     */
    'enable_mock' => env('WECHAT_ENABLE_MOCK', false),
    'mock_user' => [
        'openid' => 'obJ90wQEnl7Zb9bp9O6D73oUlLao',
        // 以下字段为 scope 为 snsapi_userinfo 时需要
        'nickname' => 'Libern',
        'sex' => '1',
        'language' => 'zh_CN',
        'city' => '上海',
        'province' => '上海',
        'country' => '中国',
        'headimgurl' => 'http://wx.qlogo.cn/mmopen/ajNVdqHZLLCROvtiaCVGR1nBVIxxDB9tRqdTmVcFRVhmt2iaU8zOK8Atgu8v5m2SdUcvnu1KW0mVeIuU1XFp8Ajg/0',
        'privilege' => [],
    ],
];
