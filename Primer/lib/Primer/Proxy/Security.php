<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/3/14
 * Time: 12:22 PM
 */

namespace Primer\Proxy;

class Security extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'security';
    }
}