<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/20/14
 * Time: 9:46 AM
 */

namespace Primer\Http;

use Primer\Utility\ParameterBag;

class HeaderBag extends ParameterBag
{
    private $_headers = array();

    private $_cookies = array();

    public function getCookies()
    {
        return $this->_cookies;
    }

    public function setCookie(Cookie $cookie)
    {
        $this->_cookies[] = $cookie;
    }
}