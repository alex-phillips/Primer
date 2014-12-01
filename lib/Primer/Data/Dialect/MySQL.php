<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/17/14
 * Time: 6:41 PM
 */

namespace Primer\Data\Dialect;

use Primer\Data\Query;
use Primer\Utility\Arrays;

class MySQL extends Dialect
{
    public function from()
    {
        return $this->_buildFrom($this->_query->type, $this->_query->table);
    }

    private function _buildFrom($type, $table)
    {
        $alias = $this->_query->aliasTable;
        if ($alias) {
            $aliasOperator = $type == Query::$DELETE ? '' : ' AS ';
            return " FROM $table" . $aliasOperator . $alias;
        }
        return " FROM $table";
    }

    public function getConnectionErrorCodes()
    {
        return array(2003, 2006);
    }

    public function getErrorCode($errorInfo)
    {
        return Arrays::getValue($errorInfo, 1);
    }
}