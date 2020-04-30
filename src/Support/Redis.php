<?php


namespace WCCS\Support;


class Redis
{
    private static $instance;

    /**
     * 私有化构造函数
     * 原因：防止外界调用构造新的对象
     */
    private function __construct(){}

    /**
     * 获取redis实例
     * @param array $options
     * @return \Redis
     */
    public static function getInstance($options = []){
        if(!self::$instance instanceof self){
            self::$instance = new self;
        }
        // 获取当前单例
        $temp = self::$instance;
        // 调用私有化方法
        return $temp->connectRedis($options);
    }

    /**
     * 连接redis
     * @param array $options
     * @return \Redis
     */
    private static function connectRedis($options = [])
    {
        try {
            $redis = new \Redis();
            $redis->connect($options['host'] ?? '127.0.0.1', $options['port'] ?? '6379');
            if (isset($options['auth'])) {
                $redis->auth($options['auth']);
            }
        }catch (\Exception $e){
            return null;
        }
        return $redis;
    }
}