<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/24/14
 * Time: 10:37 AM
 */

namespace Primer\Proxy;

class Auth extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'auth';
    }
}