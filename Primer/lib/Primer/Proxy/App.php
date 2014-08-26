<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/23/14
 * Time: 4:45 PM
 */

namespace Primer\Proxy;

class Application extends Proxy
{
    protected static function getProxyAccessor() { return 'app'; }
}