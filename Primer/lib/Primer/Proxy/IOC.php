<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/11/14
 * Time: 10:38 AM
 */

namespace Primer\Proxy;

class IOC extends Proxy
{
    protected static function getProxyAccessor() { return 'ioc'; }
}