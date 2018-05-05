<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files must retain the above copyright notice.
 */
return [
    /* 商家配置 */
    "app_id"     => "abcdefg",
    "mch_id"     => "1234567890", // 微信支付商户号
    "mch_key"    => "abcdwxyz1234567890", // 微信支付商户密钥
    "notify_url" => "123456", // 支付通知的url

    /* 系统配置 */
    "cert_pem" => '', // 证书 cert 文件路径
    "cert_key" => '', // 证书 key 文件路径

    /* 调试模式 */
    'debug'       => false, // 沙箱模式
    'debug_cache' => '', // 缓存目录配置（沙箱模式需要用到）
];
