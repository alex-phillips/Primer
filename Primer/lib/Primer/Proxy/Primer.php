<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/24/14
 * Time: 10:32 AM
 */

namespace Primer\Proxy;

class Primer extends Proxy
{
    protected static function getProxyAccessor() { return 'app'; }
}