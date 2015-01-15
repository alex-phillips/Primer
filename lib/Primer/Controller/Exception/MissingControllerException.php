<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 1:09 PM
 */

namespace Primer\Controller\Exception;

use Primer\Error\PrimerException;

/**
 * Missing Controller exception - used when a controller
 * cannot be found.
 *
 * @package       Cake.Error
 */
class MissingControllerException extends PrimerException
{
    protected $_messageTemplate = 'Controller class %s could not be found.';

    public function __construct($message, $code = 404)
    {
        parent::__construct($message, $code);
    }
}