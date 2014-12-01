<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/25/14
 * Time: 5:45 PM
 */

namespace Primer\Data\Dialect;

use Primer\Utility\Arrays;

class Postgres extends Dialect
{
    public function getConnectionErrorCodes()
    {
        return array('57000', '57014', '57P01', '57P02', '57P03');
    }

    public function getErrorCode($errorInfo)
    {
        return Arrays::getValue($errorInfo, 0);
    }
}