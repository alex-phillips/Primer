<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/12/14
 * Time: 10:46 PM
 */

namespace Primer\Proxy;

class View extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'view';
    }
}