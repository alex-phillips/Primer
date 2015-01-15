<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:14 PM
 */

namespace Primer\Data\Exception;

use Primer\Error\PrimerException;

/**
 * Exception raised when a Model could not be found.
 *
 * @package       Cake.Error
 */
class MissingModelException extends PrimerException
{
    protected $_messageTemplate = 'Model %s could not be found.';
}