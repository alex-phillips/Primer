<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:57 PM
 */

namespace Primer\View\Exception;

use Primer\Error\PrimerException;

/**
 * Used when a view file cannot be found.
 *
 * @package       Cake.Error
 */
class MissingViewException extends PrimerException
{
    protected $_messageTemplate = 'View file "%s" is missing.';
}