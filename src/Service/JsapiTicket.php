<?php
/**
 * @author zwh
 * @date 20200430
 * @desc 获取jsapi_ticket
 */


namespace Wccs\Service;


use Wccs\Center;

class JsapiTicket extends Base
{
    public function __construct($options)
    {
        parent::__construct($options);
        $access_token = Center::accessToken($options)->get();
        $this->url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
    }

    public function get()
    {
        // 先从缓存获取，如果没有再从微信服务器获取
        $jsapi_ticket = $this->redis->get($this->appid . '_jsapi_ticket');
        if (empty($jsapi_ticket)) {
            try {
                // 从微信服务器获取ticket，先进行锁定，以免并发的情况下重复获取ticket
                if ($this->lock($this->appid . '_jsapi_ticket_lock')) {
                    $result = $this->callGetApi();
                    $jsapi_ticket = $result['ticket'];
                    // 本地缓存ticket
                    $this->redis->setex($this->appid . '_jsapi_ticket', $result['expires_in'] - 200, $jsapi_ticket);
                } else {
                    // TODO 后续可能需要改进此方案
                    // 延迟10ms再获取
                    usleep(10000);
                    return $this->get();
                }
            } catch (\Exception $e) {
                // 记录错误
                $this->redis->hSet('wccs_jsapi_ticket_error', time(), $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
                return false;
            }
        }
        return $jsapi_ticket;
    }
}