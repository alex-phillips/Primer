<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/20/14
 * Time: 12:01 PM
 */

namespace Primer\Proxy;

class Cookie extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'cookie';
    }
}