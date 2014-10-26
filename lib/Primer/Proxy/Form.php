<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/3/14
 * Time: 4:51 PM
 */

namespace Primer\Proxy;

class Form extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'form';
    }

    public static function create($name, $method = "POST", $action = '', $attributes = '')
    {
        return new \Primer\Form\Form($name, $method, $action, $attributes);
    }
}