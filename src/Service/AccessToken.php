<?php
/**
 * @author zwh
 * @date 20200430
 * @desc 获取access_token
 */


namespace Wccs\Service;


use WCCS\Support\Redis;

class AccessToken extends Base
{
    public function __construct($options)
    {
        parent::__construct($options);
        $this->url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";
    }

    public function get()
    {
        // 先从缓存获取，如果没有再从微信服务器获取
        $access_token = $this->redis->get($this->appid . '_access_token');
        if (empty($access_token)) {
            try {
                // 从微信服务器获取token，先进行锁定，以免并发的情况下重复获取token
                if ($this->lock($this->appid . '_access_token_lock')) {
                    $result = $this->callGetApi();
                    $access_token = $result['access_token'];
                    // 本地缓存token
                    $this->redis->setex($this->appid . '_access_token', $result['expires_in'] - 200, $access_token);
                } else {
                    // TODO 后续可能需要改进此方案
                    // 延迟10ms再获取
                    usleep(10000);
                    return $this->get();
                }
            } catch (\Exception $e) {
                // 记录错误
                $this->redis->hSet('wccs_access_token__error', time(), $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
                return false;
            }
        }
        return $access_token;
    }
}