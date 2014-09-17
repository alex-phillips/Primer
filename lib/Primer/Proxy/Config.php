<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/16/14
 * Time: 9:58 PM
 */

namespace Primer\Proxy;

class Config extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'config';
    }
}