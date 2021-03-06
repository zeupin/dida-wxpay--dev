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
 * UnifiedOrder 统一下单
 */
class UnifiedOrder
{
    /**
     * 版本号
     */
    const VERSION = '20180612';

    /**
     * API的URL
     */
    const APIURL = "https://api.mch.weixin.qq.com/pay/unifiedorder";

    /**
     * 有效字段列表
     *
     * @var array
     */
    static $valid_fields = [
        /* 必填参数 */
        'appid'        => 'required', // 微信分配的小程序ID
        'mch_id'       => 'required', // 微信支付分配的商户id
        'trade_type'   => 'required', // 交易类型
        'out_trade_no' => 'required', // 商户订单号
        'total_fee'    => 'required', // 标价金额。单位为“分”
        'body'         => 'required', // 商品简单描述
        'notify_url'   => 'required', // 支付结果通知地址

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
    public function prepay(array $data)
    {
        // 最终提交的数据
        $temp = [];

        // 过滤，只保留指定的数值
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::$valid_fields)) {
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
        list($code, $msg) = $this->checkFields($temp);

        // 如果预检失败，直接返回
        if ($code !== 0) {
            return [1, $msg, null];
        }

        // 签名类型设为MD5
        $temp["sign_type"] = "MD5";

        // 签名
        ksort($temp);
        $temp["sign"] = Common::sign($temp, $data['sign_key']);

        // 转为xml
        $xml = Common::arrayToXml($temp);
        \Dida\Log\Log::write("request=$xml");

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
        $verify = Common::verify($rcv, $data['sign_key']);
        if ($verify === false) {
            return [1, "应答的签名校验失败", null];
        }

        // 检查prepay的result_code是否为FAIL
        if ($rcv["result_code"] == "FAIL") {
            return [$rcv["err_code"], $rcv["err_code_des"], null];
        }

        // 再次签名
        $appId = $data['appid'];
        $timeStamp = time();
        $nonceStr = Common::randomString(10);
        $package = "prepay_id={$rcv["prepay_id"]}";
        $pay = [
            'appId'     => $appId,
            'timeStamp' => "$timeStamp",
            'nonceStr'  => $nonceStr,
            'package'   => $package,
            'signType'  => 'MD5',
        ];
        ksort($pay);
        $paySign = Common::sign($pay, $data['sign_key']);
        $pay['paySign'] = $paySign;
        //unset($pay['appId']);
        // 返回
        return [0, null, $pay];
    }


    /**
     * 检查待提交数据是否满足微信支付的要求
     *
     * @return array [CMD]
     */
    protected function checkFields(array $data)
    {
        // 检查必填参数是否均已设置
        foreach (self::$valid_fields as $name => $flag) {
            switch ($flag) {
                case 'required':
                case 'auto':
                    if (!Common::field_exists($name, $data)) {
                        return [1, "必填参数 {$name} 未设置", null];
                    }
            }
        }

        // 检查条件必填参数
        if ($data["trade_type"] === "JSAPI") {
            if (!Common::field_exists('openid', $data)) {
                return [2, "JSAPI类型交易必填 openid", null];
            }
        }
        if ($data['trade_type'] === 'NATIVE') {
            if (!Common::field_exists('product_id', $data)) {
                return [2, "微信扫码支付必填 product_id", null];
            }
        }

        // 返回
        return [0, null, null];
    }
}
