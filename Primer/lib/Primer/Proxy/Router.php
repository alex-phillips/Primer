<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/19/14
 * Time: 7:21 PM
 */

namespace Primer\Proxy;

class Router extends Proxy
{
    public static function abort($code = 404, $message = '')
    {
        static::$_ioc->abort($code, $message);
    }

    protected static function getProxyAccessor()
    {
        return 'router';
    }
}