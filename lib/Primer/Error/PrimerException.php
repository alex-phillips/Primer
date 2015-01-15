<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:07 PM
 */

namespace Primer\Error;

use RuntimeException;

class PrimerException extends RuntimeException
{
    /**
     * Array of attributes that are passed in from the constructor, and
     * made available in the view when a development error is displayed.
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * Template string that has attributes sprintf()'ed into it.
     *
     * @var string
     */
    protected $_messageTemplate = '';

    /**
     * Constructor.
     *
     * Allows you to create exceptions that are treated as framework errors and disabled
     * when debug = 0.
     *
     * @param string|array $message Either the string of the error message, or an array of attributes
     *                              that are made available in the view, and sprintf()'d into CakeException::$_messageTemplate
     * @param int          $code    The code of the error, is also the HTTP status code for the error.
     */
    public function __construct($message, $code = 500)
    {
        if (is_array($message)) {
            $this->_attributes = $message;
            $message = vsprintf($this->_messageTemplate, $message);
        }
        parent::__construct($message, $code);
    }

    /**
     * Get the passed in attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }
}