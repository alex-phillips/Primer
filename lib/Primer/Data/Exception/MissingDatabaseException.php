<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:15 PM
 */

namespace Primer\Data\Exception;

use Primer\Error\PrimerException;

/**
 * Runtime Exceptions for ConnectionManager
 *
 * @package       Cake.Error
 */
class MissingDatabaseException extends PrimerException
{
    protected $_messageTemplate = 'Database connection "%s" could not be found.';
}