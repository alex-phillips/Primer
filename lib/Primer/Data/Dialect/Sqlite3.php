<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/25/14
 * Time: 5:44 PM
 */

namespace Primer\Data\Dialect;

use Primer\Utility\Arrays;
use Primer\Utility\Strings;
use Primer\Data\JoinClause;

class Sqlite3 extends Dialect
{
    public function getConnectionErrorCodes()
    {
        return array(10, 11, 14);
    }

    public function getErrorCode($errorInfo)
    {
        return Arrays::getValue($errorInfo, 1);
    }

    public function describe()
    {
        return "PRAGMA table_info(" . $this->_query->table . ")";
    }

    public function join()
    {
        $any = Arrays::any($this->_query->joinClauses, function (JoinClause $joinClause) {
                return Strings::equalsIgnoreCase($joinClause->type, 'RIGHT');
            });
        if ($any) {
            throw new \Exception('RIGHT JOIN is not supported in sqlite3.');
        }

        return parent::join();
    }
}