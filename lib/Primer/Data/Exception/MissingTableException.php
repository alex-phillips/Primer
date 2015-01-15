<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:17 PM
 */

namespace Primer\Data\Exception;

use Primer\Error\PrimerException;

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 * @package       Cake.Error
 */
class MissingTableException extends PrimerException
{
    protected $_messageTemplate = 'Table %s for model %s was not found in datasource %s.';
}