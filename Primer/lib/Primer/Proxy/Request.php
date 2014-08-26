<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/25/14
 * Time: 6:22 PM
 */

namespace Primer\Proxy;

class Request extends Proxy
{
    protected static function getProxyAccessor() { return 'request'; }
}