<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/19/14
 * Time: 7:24 PM
 */

namespace Primer\Proxy;

use Primer\Core\Object;

abstract class Proxy extends Object
{
    protected static $_app;

    public static function setApp($app)
    {
        static::$_app = $app;
    }

    public static function __callStatic($method, $args = array())
    {
        $proxy = static::getProxy();

        return call_user_func_array(array($proxy, $method), $args);
    }

    protected static function getProxy()
    {
        return static::$_app[static::getProxyAccessor()];
    }

    protected static function getAccessor()
    {
        throw new \RuntimeException("Proxy not available");
    }
}