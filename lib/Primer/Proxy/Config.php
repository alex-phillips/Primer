<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/12/14
 * Time: 8:37 PM
 */

namespace Primer\Proxy;

class Config extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'config';
    }
}