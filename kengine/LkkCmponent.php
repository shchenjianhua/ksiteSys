<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/9/16
 * Time: 13:03
 * Desc: -lkk 组件类
 */


namespace Kengine;

use Phalcon\Crypt as PhCrypt;
use Phalcon\Di\FactoryDefault\Cli as CliDi;
use Phalcon\Mvc\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Lkk\Phalwoo\Phalcon\Cache\Backend\Redis as BackendRedis;

class LkkCmponent {

    //类实例
    private static $objects = [];

    /**
     * 单例 crypt
     * @return mixed
     */
    public static function crypt() {
        if(!isset(self::$objects[__FUNCTION__]) ) {
            $crypt = new PhCrypt();
            $conf = getConf('crypt');
            $crypt->setKey($conf->key);
            $crypt->setPadding(PhCrypt::PADDING_ZERO);
            self::$objects[__FUNCTION__] = $crypt;
        }

        return self::$objects[__FUNCTION__];
    }


    /**
     * 单例 CLI应用DI容器
     * @return mixed
     */
    public static function cliDi() {
        if(!isset(self::$objects[__FUNCTION__]) ) {
            $di = new CliDi();
            self::$objects[__FUNCTION__] = $di;
        }

        return self::$objects[__FUNCTION__];
    }


    /**
     * 单例 url
     * @return mixed
     */
    public static function url() {
        if(!isset(self::$objects[__FUNCTION__]) ) {
            $conf = getConf('site');
            $url = new Url();
            $url->setBaseUri($conf->url);
            $url->setBasePath('/');

            self::$objects[__FUNCTION__] = $url;
        }

        return self::$objects[__FUNCTION__];
    }


    /**
     * 单例 当前站点缓存操作类
     * @return mixed
     */
    public static function siteCache() {
        if(!isset(self::$objects[__FUNCTION__]) ) {
            $cacheConf = getConf('cache');

            $frontCache = new FrontendData([
                'lifetime' => $cacheConf->lifetime,
            ]);

            self::$objects[__FUNCTION__] = new BackendRedis($frontCache, $cacheConf);
        }

        return self::$objects[__FUNCTION__];
    }


    /**
     * 单例 站群缓存操作类
     * @return mixed
     */
    public static function sysCache() {
        if(!isset(self::$objects[__FUNCTION__]) ) {
            $cacheConf = getConf('cache');

            $frontCache = new FrontendData([
                'lifetime' => $cacheConf->lifetime,
            ]);

            $initConf = [
                'redis'     => 'redis_system', //redis连接池名称,参考pool配置
            ];

            self::$objects[__FUNCTION__] = new BackendRedis($frontCache, $initConf);
        }

        return self::$objects[__FUNCTION__];
    }



    /**
     * 单例 同步模式-主库
     * @param string $requestUuid
     *
     * @return mixed
     */
    public static function SyncDbMaster(string $requestUuid ='') {
        $key = $requestUuid . __FUNCTION__;
        if(!isset(self::$objects[$key]) ) {
            $conf = getConf('pool');
            $db = new Mysql([
                'host'      => $conf->mysql_master->args->host,
                'port'      => $conf->mysql_master->args->port,
                'username'  => $conf->mysql_master->args->user,
                'password'  => $conf->mysql_master->args->password,
                'dbname'    => $conf->mysql_master->args->database,
                'charset'   => $conf->mysql_master->charset,
            ]);

            self::$objects[$key] = $db;
        }

        return self::$objects[$key];
    }



    /**
     * 单例 同步模式-从库
     * @param string $requestUuid
     *
     * @return mixed
     */
    public static function SyncDbSlave(string $requestUuid ='') {
        $key = $requestUuid . __FUNCTION__;
        if(!isset(self::$objects[$key]) ) {
            $conf = getConf('pool');
            $db = new Mysql([
                'host'      => $conf->mysql_slave->args->host,
                'port'      => $conf->mysql_slave->args->port,
                'username'  => $conf->mysql_slave->args->user,
                'password'  => $conf->mysql_slave->args->password,
                'dbname'    => $conf->mysql_slave->args->database,
                'charset'   => $conf->mysql_slave->charset,
            ]);

            self::$objects[$key] = $db;
        }

        return self::$objects[$key];
    }



    /**
     * 销毁某个请求相关的组件对象
     * @param string $requestUuid
     */
    public static function destroyRequests(string $requestUuid) {
        foreach (self::$objects as $key=>$object) {
            if(stripos($key, $requestUuid)!==false) {
                $object = null;
                unset(self::$objects[$key]);
            }
        }
    }




}