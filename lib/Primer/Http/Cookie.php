<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/20/14
 * Time: 9:50 AM
 */

namespace Primer\Http;

use Primer\Utility\ParameterBag;

class Cookie extends ParameterBag
{
    private $_name;
    private $_value;
    private $_expire;
    private $_path;
    private $_domain;
    private $_secure;
    private $_httpOnly;

    public static function create()
    {
        return new static();
    }

    public function make($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httpOnly = null)
    {
        // convert expiration time to a Unix timestamp
        if ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        }
        elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);

            if ($expire === false || $expire === -1) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        if ($domain === null) {
            $domain = apache_request_headers()['Host'];
        }

        $this->_name = $name;
        $this->_value = $value;
        $this->_expire = $expire;
        $this->_path = empty($path) ? '/' : $path;
        $this->_domain = $domain;
        $this->_secure = (bool)$secure;
        $this->_httpOnly = (bool)$httpOnly;

        return $this;
    }

    /**
     * @return null
     */
    public function getDomain()
    {
        return $this->_domain;
    }

    /**
     * @return int|string
     */
    public function getExpire()
    {
        return $this->_expire;
    }

    /**
     * @return boolean
     */
    public function isHttpOnly()
    {
        return $this->_httpOnly;
    }

    /**
     * @return array
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return $this->_secure;
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->_value;
    }
}