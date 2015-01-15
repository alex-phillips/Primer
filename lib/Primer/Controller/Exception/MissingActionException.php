<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 5:01 PM
 */

namespace Primer\Controller\Exception;

use Primer\Error\PrimerException;

/**
 * Missing Action exception - used when a controller action
 * cannot be found.
 *
 * @package       Cake.Error
 */
class MissingActionException extends PrimerException
{

    protected $_messageTemplate = 'Action %s::%s() could not be found.';

    public function __construct($message, $code = 404)
    {
        parent::__construct($message, $code);
    }
}