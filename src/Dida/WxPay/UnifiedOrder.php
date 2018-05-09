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
 * UnifiedOrder 微信统一下单
 */
class UnifiedOrder
{
    /**
     * 版本号
     */
    const VERSION = '20180505';

    /**
     * API的URL
     */
    const APIURL = "https://api.mch.weixin.qq.com/pay/unifiedorder";

    /**
     * 有效的字段名
     *
     * @var array
     */
    static $fieldset = [
        /* 必填参数 */
        'appid'        => '', // 微信分配的小程序ID
        'mch_id'       => '', // 微信支付分配的商户id
        'trade_type'   => '', // 交易类型
        'out_trade_no' => '', // 商户订单号
        'total_fee'    => '', // 标价金额。单位为“分”
        'body'         => '', // 商品简单描述
        'notify_url'   => '', // 支付结果通知地址

        /* 自动生成的必填参数 */
        'nonce_str'        => 'auto', // 随机字符串
        'spbill_create_ip' => 'auto', // 付款用户的终端IP

        /* 在一定条件的必填参数 */
        'product_id' => 'cond', // 商品ID。扫码支付时必填
        'openid'     => 'cond', // 用户标识。JSAPI方式时必填

        /* 可选参数 */
        'device_info' => 'optional', // 自定义的当前设备字段
        'detail'      => 'optional', // 商品详情
        'attach'      => 'optional', // 附加数据
        'fee_type'    => 'optional', // 标价币种。默认为CNY
        'time_start'  => 'optional', // 订单创建时间。格式 yyyymmddhhmmss
        'time_expire' => 'optional', // 订单失效时间。格式 yyyymmddhhmmss
        'goods_tag'   => 'optional', // 订单优惠标记
        'limit_pay'   => 'optional', // 限制支付方式，如：不可使用信用卡

        /* 计算字段 */
        'sign_type' => 'calc', // 签名类型。默认为MD5。可选MD5和HMAC-SHA256
        'sign'      => 'calc', // 签名
    ];


    /**
     * 提交支付申请
     *
     * @param array $data 业务数据
     *
     * @return array [$code, $msg, $result]
     */
    public function apply(array $data)
    {
        // 最终提交的数据
        $temp = [];

        // 过滤，只保留指定的数值
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::$fieldset)) {
                $temp[$key] = $value;
            }
        }

        // auto类型的字段
        if (!isset($temp['spbill_create_ip']) || !$temp['spbill_create_ip']) {
            $temp['spbill_create_ip'] = Common::clientIP();
        }
        if (!isset($temp['nonce_str']) || !$temp['nonce_str']) {
            $temp['nonce_str'] = Common::randomString(10);
        }

        // 预检
        list($code, $msg) = $this->check($temp);

        // 如果预检失败，直接返回
        if ($code !== 0) {
            return [1, $msg, null];
        }

        // 签名类型设为MD5
        $temp["sign_type"] = "MD5";

        // 签名
        $temp["sign"] = Common::sign($temp, $data['sign_key']);

        // 转为xml
        $xml = Common::arrayToXml($temp);
        \Dida\Log\Log::write("request=$xml");

        // 联机申请
        $curl = new \Dida\CURL\CURL();
        $response = $curl->request([
            'url'    => self::APIURL,
            'method' => 'POST',
            'data'   => $xml,
        ]);
        \Dida\Log\Log::write($response);

        // 返回
        return $response;
    }


    /**
     * 检查待提交数据是否满足微信支付的要求
     *
     * @return array [$code, $msg]
     */
    protected function check(array $data)
    {
        // 检查必填参数是否均已设置
        foreach (self::$fieldset as $name => $flag) {
            switch ($flag) {
                case '':
                case 'auto':
                    if (!Common::field_exists($name, $data)) {
                        return [1, "缺少必填参数 {$name}"];
                    }
            }
        }

        // 检查条件必填参数
        if ($data["trade_type"] === "JSAPI") {
            if (!Common::field_exists('openid', $data)) {
                return [2, "JSAPI类型交易必填 openid"];
            }
        }
        if ($data['trade_type'] === 'NATIVE') {
            if (!Common::field_exists('product_id', $data)) {
                return [2, "微信扫码支付必填 product_id"];
            }
        }

        // 返回
        return [0, null];
    }
}
