<?php
/**
 * @author zwh
 * @date 20200430
 * @desc 基类
 */


namespace Wccs\Service;


use Wccs\Support\Http;
use WCCS\Support\Redis;

class Base
{
    /**
     * 请求路径
     * @var string
     */
    protected $url;

    /**
     * 是否已重试
     * @var bool
     */
    protected $is_try;

    /**
     * 当前请求方法参数
     * @var array
     */
    protected $current_method = [];

    /**
     * redis实例
     * @var array
     */
    protected $redis = null;

    /**
     * 微信appid
     * @var array
     */
    protected $appid;

    /**
     * 微信secret
     * @var array
     */
    protected $appsecret;

    protected function __construct($options)
    {
        if (empty($options['wechat_options']['appid'])) {
            throw new \Exception('缺少appid参数');
        }
        if (empty($options['wechat_options']['appsecret'])) {
            throw new \Exception('缺少appsecret参数');
        }
        $this->appid = $options['wechat_options']['appid'];
        $this->appsecret = $options['wechat_options']['appsecret'];
        $this->redis = Redis::getInstance($options['redis_options'] ?? []);
    }

    /**
     * 接口通用POST请求方法
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    protected function callPostApi($data = [], $options = [])
    {
        $this->registerApi(__FUNCTION__, func_get_args());
        return $this->httpPost($data, $options);
    }

    /**
     * 接口通用GET请求方法
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    protected function callGetApi($data = [], $options = [])
    {
        $this->registerApi(__FUNCTION__, func_get_args());
        return $this->httpGet($data, $options);
    }

    /**
     * 以POST获取接口数据
     * @param $data
     * @param array $options
     * @return array|mixed
     */
    protected function httpPost($data, $options = [])
    {
        $result = Http::doPost($this->url, $data, $options);
        if (isset($result['error'])) {
            if (isset($this->current_method['method']) && empty($this->is_try)) {
                $this->is_try = true;
                return call_user_func_array([$this, $this->current_method['method']], $this->current_method['arguments']);
            }
        }
        return $result;
    }

    /**
     * 以GET获取接口数据
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    protected function httpGet($data = [], $options = [])
    {
        $result = Http::doGet($this->url, $data, $options);
        if (isset($result['error'])) {
            if (isset($this->currentMethod['method']) && empty($this->is_try)) {
                $this->is_try = true;
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
        }
        return $result;
    }

    /**
     * 注册当前请求接口
     * @param $method
     * @param array $arguments
     */
    protected function registerApi($method, $arguments = [])
    {
        $this->current_method = ['method' => $method, 'arguments' => $arguments];
    }

    protected function lock($key)
    {
        //初步加锁
        $expire_time = 3;
        $is_lock = $this->redis->setnx($key, time() + $expire_time);
        if ($is_lock) {
            return true;
        } else {
            //加锁失败的情况下。判断锁是否已经存在，如果锁存在切已经过期，那么删除锁。进行重新加锁
            $val = $this->redis->get($key);
            if($val && $val < time()) {
                $this->redis->del($key);
            }
            return $this->redis->setnx($key, time() + $expire_time);
        }
    }
}