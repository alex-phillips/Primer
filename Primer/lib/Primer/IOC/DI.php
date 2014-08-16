<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/11/14
 * Time: 10:38 AM
 */

namespace Primer\IOC;

class DI
{
    private static $_ioc;

    public static function init()
    {
        self::$_ioc = new IOC();
    }

    public static function bind($key, $o)
    {
        self::$_ioc->bind($key, $o);
    }

    public static function singleton($key, $o)
    {
        self::$_ioc->singleton($key, $o);
    }

    public static function instance($key, $o)
    {
        self::$_ioc->isntance($key, $o);
    }

    public static function make($key, $parameters = array())
    {
        return self::$_ioc->make($key, $parameters);
    }

    public static function alias($alias, $binding)
    {
        return self::$_ioc->alias($alias, $binding);
    }
}