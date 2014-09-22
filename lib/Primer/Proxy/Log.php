<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/22/14
 * Time: 6:06 PM
 */

namespace Primer\Proxy;

class Log extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'logger';
    }
}