<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 12:15 PM
 */

namespace Primer\Data\Exception;

use Primer\Error\PrimerException;

/**
 * Exception class to be thrown when a datasource configuration is not found
 *
 * @package       Cake.Error
 */
class MissingDatasourceConfigException extends PrimerException
{
    protected $_messageTemplate = 'The datasource configuration "%s" was not found in database.php';
}