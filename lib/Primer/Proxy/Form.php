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
        $form = new \Primer\Form\Form($name, $method, $action, $attributes);
        $form->assetsPath(APP_ROOT . '/public/libs/Form/', '/libs/Form/');
        return $form;
    }
}