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
 * MiniAppGateway
 */
class MiniAppGateway extends Gateway
{


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
    public function apply(array $data)
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
        $result = $uniorder->apply($temp);

        // 返回申请结果
        return $result;
    }
}
