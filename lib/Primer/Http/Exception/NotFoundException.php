<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 10:15 AM
 */

namespace Primer\Http\Exception;

class NotFoundException extends HttpException
{
    public function __construct($message = null, $code = 404) {
        if (empty($message)) {
            $message = 'Not Found';
        }
        parent::__construct($message, $code);
    }
}