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
}