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
 * MiniAppGateway 微信小程序支付网关
 */
class MiniAppGateway
{
    protected $conf = [];


    /**
     * 设置配置项
     *
     * @param array $conf
     */
    public function config(array $conf)
    {
        $this->conf = $conf;
    }


    /**
     * 申请支付
     *
     * @param array $data
     */
    public function prepay(array $data)
    {
        // 预置参数
        $preset = [
            "trade_type" => "JSAPI",
            "appid"      => $this->conf["app_id"],
            "mch_id"     => $this->conf["mch_id"],
            'notify_url' => $this->conf["notify_url"],
            'sign_type'  => 'MD5',
            'sign_key'   => $this->conf["mch_key"],
        ];

        // 准备数据
        $temp = array_merge($data, $preset);

        // 创建一个统一下单对象
        $uniorder = new UnifiedOrder;

        // 申请
        $result = $uniorder->prepay($temp);

        // 返回申请结果
        return $result;
    }


    /**
     * 当收到了微信发来的支付结果通知，将其解析出来
     *
     * @param string $xml
     *
     * 如下是个微信发回的支付通知数据(敏感数据用...代替)
     *
      <xml>
      <appid><![CDATA[...]]></appid>
      <bank_type><![CDATA[CFT]]></bank_type>
      <cash_fee><![CDATA[34]]></cash_fee>
      <fee_type><![CDATA[CNY]]></fee_type>
      <is_subscribe><![CDATA[N]]></is_subscribe>
      <mch_id><![CDATA[...]]></mch_id>
      <nonce_str><![CDATA[rY91BsyISN]]></nonce_str>
      <openid><![CDATA[...]]></openid>
      <out_trade_no><![CDATA[...]]></out_trade_no>
      <result_code><![CDATA[SUCCESS]]></result_code>
      <return_code><![CDATA[SUCCESS]]></return_code>
      <sign><![CDATA[...]]></sign>
      <time_end><![CDATA[20180605101716]]></time_end>
      <total_fee>34</total_fee>
      <trade_type><![CDATA[JSAPI]]></trade_type>
      <transaction_id><![CDATA[...]]></transaction_id>
      </xml>
     *
     * @return [CMD] 如果验证xml是可信的，返回xml对应的关联数组
     */
    public function parseNotify($xml, $mch_key)
    {
        // 把xml转为数组形式
        $msg = Common::xmlToArray($xml);

        // 如果xml无效
        if ($msg === false) {
            \Dida\Log\Log::write("无效的微信支付通知xml");
            \Dida\Log\Log::write($xml);
            return [1, "支付通知不是一个有效的xml", null];
        }

        // 校验签名
        $result = Common::verify($msg, $mch_key);

        // 如果签名正确，返回$msg
        // 业务数据是否正确，不是在这里验证，是在具体app中验证。
        // 这里只关心微信端返回的数据是否可信。
        if ($result) {
            return [0, null, $msg];
        } else {
            return [1, "验证支付结果通知的签名失败，此消息不被信任", $msg];
        }
    }


    /**
     * 支付结果通知符合预期，可以调用本函数，向腾讯端发送一个成功应答。
     * 腾讯端收到成功应答后，后面就不再发送同一交易的结果通知了。
     *
     * @return string
     */
    public function notifyOK()
    {
        return <<<TEXT
<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>
TEXT;
    }


    /**
     * 支付结果通知失败，可以调用本函数，发送一个失败应答。
     *
     * @param string $errinfo 具体失败原因，如： 签名失败, 参数格式校验错误 等。
     *
     * @return string
     */
    public function notifyFail($errinfo)
    {
        $response = [
            "return_code" => "FAIL",
            "return_msg"  => $errinfo
        ];

        return Common::arrayToXml($response);
    }
}
