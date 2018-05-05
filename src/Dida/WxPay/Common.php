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
 * Common
 */
class Common
{


    /**
     * 生成随机字符串
     *
     * @param int $num
     * @param string $set
     */
    public static function randomString($num = 32, $set = null)
    {
        if (!$set) {
            $set = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        $len = strlen($set);
        $r = [];
        for ($i = 0; $i < $num; $i++) {
            $r[] = substr($set, mt_rand(0, $len - 1), 1);
        }
        return implode('', $r);
    }


    /**
     * 对数据进行签名
     *
     * @param array $data
     * @param string $sign_key
     *
     * @return string
     */
    public static function sign(array $data, $sign_key)
    {
        $temp = [];

        // 滤除为空的参数
        foreach ($data as $k => $v) {
            if ($v) {
                $temp[$k] = $v;
            }
        }

        // sign不参与校验
        unset($temp["sign"]);

        // 把键值按照ASCII码排序
        ksort($temp);

        // 加上key
        $temp["key"] = $sign_key;

        // 生成raw
        $raw = http_build_query($temp);

        var_dump($raw);

        // hash
        $hash = md5($raw);

        // 转为大写
        $hash = strtoupper($hash);

        return $hash;
    }


    /**
     * 将array格式转为xml格式
     *
     * @param array $data
     */
    public static function toXml(array $data)
    {
        $output = [];

        $output[] = "<xml>";
        foreach ($data as $name => $value) {
            $output[] = "<$name>" . urlencode($value) . "</$name>";
        }
        $output[] = "</xml>";

        return implode('', $output);
    }


    /**
     * 获取客户端的ip
     *
     * @return string|false 成功返回客户端ip，失败返回false
     */
    public static function clientIP()
    {
        $ip = false;

        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_X_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
        } elseif (isset($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        return $ip;
    }


    /**
     * 检查数据中的指定字段是否存在，且不为空
     *
     * @param string $field
     * @param array $data
     */
    public static function field_exists($field, array $data)
    {
        return (isset($data[$field]) && $data[$field]);
    }
}
