<?php
/**
 * @author zwh
 * @date 20200430
 * @desc 静态调用
 */

namespace Wccs;


/**
 * @method \Wccs\Service\AccessToken accessToken($options = []) static
 * @method \Wccs\Service\JsapiTicket jsapiTicket($options = []) static
 */
class Center
{
    /**
     * 静态魔术加载方法
     * @param string $name 静态类名
     * @param array $arguments 参数集合
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $name = ucfirst($name);
        $class = 'Wccs\\Service\\' . $name;
        if (!class_exists($class)) {
            throw new \Exception('服务不存在');
        }
        $option = array_shift($arguments);
        return new $class($option);
    }
}