<?php
/**
 * @author zwh
 * @date 20200430
 * @desc http请求封装
 */

namespace Wccs\Support;

class Http
{
    /**
     * @param $url
     * @param $data
     * @param array $options
     * @return array|mixed
     */
    public static function doPost($url, $data, $options = [])
    {
        return self::doCurl($url, 'post', $data, $options);
    }

    /**
     * @param $url
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    public static function doGet($url, $data = [], $options = [])
    {
        return self::doCurl($url, 'get', $data, $options);
    }

    private static function doCurl($url, $method, $data, $options = [])
    {
        $ch = curl_init();
        if (strtolower($method) === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (strtolower($method) === 'get') {
            if ($data) {
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                $url .= '?' . $data;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        // 请求头设置
        if (!empty($options['header'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['header']);
        }
        // 证书文件设置
        if (!empty($options['ssl_cert'])) {
            if (file_exists($options['ssl_cert'])) {
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLCERT, $options['ssl_cert']);
            } else {
                return [
                    'error' => true,
                    'error_code' => '9999',
                    'error_message' => '[ssl_cert] 文件不存在'
                ];
            }
        }
        // 证书文件设置
        if (!empty($options['ssl_key'])) {
            if (file_exists($options['ssl_key'])) {
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLKEY, $options['ssl_key']);
            } else {
                return [
                    'error' => true,
                    'error_code' => '9999',
                    'error_message' => '[ssl_key] 文件不存在'
                ];
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($options['timeout']) ? $options['timeout'] : 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $err_code = curl_errno($ch);
        $err_msg = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if($response === false) {
            $result = [
                'error' => true,
                'error_code' => $err_code,
                'error_message' => $err_msg
            ];
        } elseif(empty($response)){
            $result = [
                'error' => true,
                'error_code' => '9999',
                'error_message' => 'No Response.'
            ];
        } elseif ($info['http_code'] != 200) {
            $result = [
                'error' => true,
                'error_code' => '9999',
                'error_message' => 'HTTP ERROR ' . $info['http_code']
            ];
        } else{
            $result = json_decode($response,true);
        }
        return $result;
    }
}