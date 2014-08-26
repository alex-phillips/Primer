<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/19/14
 * Time: 7:24 PM
 */

namespace Primer\Proxy;

abstract class Proxy
{
    protected static $_ioc;

    protected static $_aliases = array();

    public static function setIOC($ioc)
    {
        static::$_ioc = $ioc;
    }

    public static function register($class, $proxy)
    {
        static::$_aliases[$class] = $proxy;
    }

    public static function __callStatic($method, $args = array())
    {
        $proxy = static::getProxy();

        return call_user_func_array(array($proxy, $method), $args);
    }

    protected static function getProxy()
    {
        return static::$_ioc[static::getProxyAccessor()];
    }

    protected static function getAccessor()
    {
        throw new \RuntimeException("Proxy not available");
    }
}