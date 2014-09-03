<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 8/30/14
 * Time: 7:38 AM
 */

namespace Primer\Proxy;

class Mail extends Proxy
{
    protected static function getProxyAccessor()
    {
        return 'mail';
    }
}