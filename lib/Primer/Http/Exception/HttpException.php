<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 10:13 AM
 */

namespace Primer\Http\Exception;

use Primer\Error\PrimerException;

class HttpException extends PrimerException
{
}

/**
 * Represents an HTTP 400 error.
 *
 * @package       Cake.Error
 */
class BadRequestException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Bad Request' will be the message
     * @param int    $code    Status code, defaults to 400
     */
    public function __construct($message = null, $code = 400)
    {
        if (empty($message)) {
            $message = 'Bad Request';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Represents an HTTP 401 error.
 *
 * @package       Cake.Error
 */
class UnauthorizedException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Unauthorized' will be the message
     * @param int    $code    Status code, defaults to 401
     */
    public function __construct($message = null, $code = 401)
    {
        if (empty($message)) {
            $message = 'Unauthorized';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Represents an HTTP 403 error.
 *
 * @package       Cake.Error
 */
class ForbiddenException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Forbidden' will be the message
     * @param int    $code    Status code, defaults to 403
     */
    public function __construct($message = null, $code = 403)
    {
        if (empty($message)) {
            $message = 'Forbidden';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Represents an HTTP 404 error.
 *
 * @package       Cake.Error
 */
class NotFoundException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Not Found' will be the message
     * @param int    $code    Status code, defaults to 404
     */
    public function __construct($message = null, $code = 404)
    {
        if (empty($message)) {
            $message = 'Not Found';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Represents an HTTP 405 error.
 *
 * @package       Cake.Error
 */
class MethodNotAllowedException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Method Not Allowed' will be the message
     * @param int    $code    Status code, defaults to 405
     */
    public function __construct($message = null, $code = 405)
    {
        if (empty($message)) {
            $message = 'Method Not Allowed';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Represents an HTTP 500 error.
 *
 * @package       Cake.Error
 */
class InternalErrorException extends HttpException
{

    /**
     * Constructor
     *
     * @param string $message If no message is given 'Internal Server Error' will be the message
     * @param int    $code    Status code, defaults to 500
     */
    public function __construct($message = null, $code = 500)
    {
        if (empty($message)) {
            $message = 'Internal Server Error';
        }
        parent::__construct($message, $code);
    }

}

/**
 * Private Action exception - used when a controller action
 * starts with a  `_`.
 *
 * @package       Cake.Error
 */
class PrivateActionException extends PrimerException
{

    protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

    public function __construct(
        $message, $code = 404, Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Used when a layout file cannot be found.
 *
 * @package       Cake.Error
 */
class MissingLayoutException extends PrimerException
{
    protected $_messageTemplate = 'Layout file "%s" is missing.';
}

/**
 * Exception class for Router. This exception will be thrown from Router when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class RouterException extends PrimerException
{
}