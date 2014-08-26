<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/23/14
 * Time: 5:05 PM
 */

namespace Primer\Proxy;

class Session extends Proxy
{
    protected static function getProxyAccessor() { return 'session'; }
}