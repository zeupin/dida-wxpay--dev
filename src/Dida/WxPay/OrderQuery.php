<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace Dida\WxPay;

/**
 * OrderQuery
 */
class OrderQuery
{
    /**
     * 版本号
     */
    const VERSION = '20180611';

    /**
     * API的URL
     */
    const APIURL = "https://api.mch.weixin.qq.com/pay/orderquery";

    /**
     * 有效的字段列表
     *
     * @var array
     */
    static $valid_fields = [
        /* 必填参数 */
        'appid'  => 'required', // 微信分配的小程序ID
        'mch_id' => 'required', // 微信支付分配的商户id

        /* 自动生成的必填参数 */
        'nonce_str' => 'auto', // 随机字符串

        /* 查询条件，二选一，同时提供时，优先使用transaction_id */
        'transaction_id' => '', // 微信订单号
        'out_trade_no'   => '', // 商户订单号

        /* 计算字段 */
        'sign_type' => 'calc', // 签名类型。默认为MD5。可选MD5和HMAC-SHA256
        'sign'      => 'calc', // 签名
    ];


    /**
     * 查询一个微信支付的执行结果
     *
     * @param array $biz 业务数据
     * @param array $conf  配置
     */
    public function query(array $biz, array $conf)
    {
        // 初始数据
        $data = [
            "appid"     => $conf["app_id"],
            "mch_id"    => $conf["mch_id"],
            'sign_type' => 'MD5',
            "nonce_str" => Common::randomString(10),
        ];

        // 和业务数据合并
        $data = array_merge_recursive($biz, $data);

        // 签名
        $data["sign"] = Common::sign($data, $conf["sign_key"]);

        // 转为xml
        $xml = Common::arrayToXml($data);

        // 联机提交
        $curl = new \Dida\CURL\CURL();
        $result = $curl->request([
            'url'    => self::APIURL,
            'method' => 'POST',
            'data'   => $xml,
        ]);
        \Dida\Log\Log::write($result);

        // 解析应答
        list($code, $msg, $xml) = $result;

        // 如果应答有错，直接返回
        if ($code !== 0) {
            return [$code, $msg, null];
        }

        // 验证应答是否合法
        $rcv = Common::xmlToArray($xml);
        $verify = Common::verify($rcv, $conf['sign_key']);
        if ($verify === false) {
            return [1, "应答的签名校验失败", null];
        }

        // 返回应答结果
        return [0, null, $rcv];
    }


    /**
     * 检查要提交的字段是否满足要求
     *
     * @param array $fields
     *
     * @return [CMD]
     */
    public function checkFields(array $fields)
    {
        // 检查是否含有无效字段
        foreach ($fields as $name => $value) {
            if (!array_key_exists($name, self::$valid_fields)) {
                return [1, "发现无效字段 $name", null];
            }
        }

        // 检查必填参数是否均已设置
        foreach (self::$valid_fields as $name => $flag) {
            switch ($flag) {
                case 'required':
                case 'auto':
                    if (!Common::field_exists($name, $fields)) {
                        return [1, "必填参数 {$name} 未设置", null];
                    }
            }
        }

        // 通过
        return [0, null, null];
    }
}
