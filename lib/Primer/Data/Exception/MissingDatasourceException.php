<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:17 PM
 */

namespace Primer\Data\Exception;

use Primer\Error\PrimerException;

/**
 * Used when a datasource cannot be found.
 *
 * @package       Cake.Error
 */
class MissingDatasourceException extends PrimerException
{
    protected $_messageTemplate = 'Datasource class %s could not be found. %s';
}