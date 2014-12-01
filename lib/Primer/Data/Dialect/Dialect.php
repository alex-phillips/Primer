<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/19/14
 * Time: 5:20 PM
 */

namespace Primer\Data\Dialect;

use Primer\Data\JoinClause;
use Primer\Data\Query;
use Primer\Utility\FluentArray;

abstract class Dialect
{
    /**
     * @var Query
     */
    protected $_query;
    protected $_bindings = array();
    protected $_insertColumns = array();
    protected $_updateAttributes = array();

    public function select()
    {
        if ($this->_query->type == Query::$SELECT) {
            return 'SELECT ' .
            ($this->_query->distinct ? 'DISTINCT ' : '') .
            (empty($this->_query->selectColumns) ? '*' : implode(', ', $this->_query->selectColumns));
        }
        if ($this->_query->type == Query::$COUNT) {
            return 'SELECT count(*)';
        }

        return '';
    }

    public function update()
    {
        $this->buildInsertAttributes($this->_query->updateAttributes);

        return "UPDATE {$this->_query->table} SET " . implode(
            ', ',
            $this->_updateAttributes
        );
    }

    public function insert()
    {
        $this->buildInsertAttributes($this->_query->updateAttributes);

        return "INSERT INTO {$this->_query->table} (" . implode(', ', $this->_insertColumns) . ") VALUES (" . implode(',', array_keys($this->_bindings)) . ")";
    }

    public function delete()
    {
        $this->_query->aliasTable = '';

        return "DELETE";
    }

    public function join()
    {
        $join = $this->buildJoinQuery($this->_query->joinClauses);
        if ($join) {
            return ' ' . $join;
        }
        return '';
    }

    public function buildJoinQuery($joinClauses)
    {
        $elements = FluentArray::from($joinClauses)
            ->map('Primer\Data\Dialect\Dialect::buildJoinQueryPart')
            ->toArray();
        return implode(" ", $elements);
    }

    public static function buildJoinQueryPart(JoinClause $joinClause)
    {
        $alias = $joinClause->alias ? " AS {$joinClause->alias}" : "";
        $on = self::buildWhereQuery($joinClause->onClauses);
        if ($joinClause->alias) {
            $on = preg_replace("#(?<=^| ){$joinClause->joinTable}(?=\\.)#", $joinClause->alias, $on);
        }

        return $joinClause->type . ' JOIN ' . $joinClause->joinTable . $alias . ' ON ' . $joinClause->getJoinColumnWithTable() . ' = ' . $joinClause->getJoinedColumnWithTable() . ($on ? " AND $on" : '');
    }

    public function where()
    {
        if ($this->_query->whereClauses) {
            return " WHERE " . $this->buildWhereQuery($this->_query->whereClauses);
        }

        return '';
    }

    protected function buildInsertAttributes($attributes)
    {
        foreach ($attributes as $col => $val) {
            $this->_bindings[":$col"] = $val;

            // Columns are used for INSERT
            $this->_insertColumns[] = $col;
            // Set array is used for UPDATE
            $this->_updateAttributes[] = "$col = :$col";
        }
    }

    protected function buildWhereQuery($conditions, $conjunction = '')
    {
        // @TODO: $this->User->find( 'all', array(
        //        'conditions' => array("not" => array ( "User.site_url" => null)
        //    ))
        //
        // Add 'not' functionality
        $aliasPrefix = '';
        if ($this->_query->aliasTable) {
            $aliasPrefix = $this->_query->aliasTable . ".";
        }

        $retval = array();
        foreach ($conditions as $k => $v) {
            if ((strtoupper($k) === 'OR' || strtoupper($k) === 'AND') && is_array($v)) {
                $retval[] = '(' . $this->buildWhereQuery(
                        $v,
                        strtoupper($k)
                    ) . ')';
            }
            else if ($conjunction == 'NOT') {
                // @TODO: need to fully test this - not ready for production
                if (is_array($v)) {
//                    $v = implode(',', $v);
//                    return ""
                }
                else {
                    $this->_bindings[":$k" . sizeof($this->_bindings)] = $v;

                    return $aliasPrefix . "$k IS NOT :$v";
                }
            }
            else {
                if (is_array($v)) {
                    $retval[] = $this->buildWhereQuery($v);
                }
                else {
                    $binding = $k;
                    $operators = array(
                        'LIKE',
                        '!=',
                    );
                    if (preg_match('#\s+(' . implode('|', $operators) . ')\s*$#', $k, $matches)) {
                        $binding = preg_replace("#{$matches[0]}#", '', $k);
                        switch ($matches[1]) {
                            case 'LIKE':
                                $v = "%$v%";
                                break;
                        }
                        $retval[] = $aliasPrefix . "$k :$binding" . sizeof($this->_bindings) . "";
                    }
                    else {
                        $retval[] = $aliasPrefix . "$k = :$binding" . sizeof($this->_bindings) . "";
                    }
                    $this->_bindings[":$binding" . sizeof($this->_bindings)] = $v;
                }
            }
        }

        return implode(" $conjunction ", $retval);
    }

    public function groupBy()
    {
        $groupBy = $this->_query->groupBy;
        if ($groupBy) {
            return ' GROUP BY ' . (is_array($groupBy) ? implode(', ', $groupBy) : $groupBy);
        }
        return '';
    }

    public function order()
    {
        $order = $this->_query->order;
        if ($order) {
            return ' ORDER BY ' . (is_array($order) ? implode(', ', $order) : $order);
        }
        return '';
    }

    public function limit()
    {
        if ($this->_query->limit) {
            return ' LIMIT ' . $this->_query->limit;
        }

        return '';
    }

    public function offset()
    {
        if ($this->_query->offset) {
            return ' OFFSET ' . $this->_query->offset;
        }

        return '';
    }

    public function from()
    {
        $alias = $this->_query->aliasTable ? ' AS ' . $this->_query->aliasTable : '';
        return ' FROM ' . $this->_query->table . $alias;
    }

    public function describe()
    {
        return "DESCRIBE " . $this->_query->table;
    }

    public function buildQuery(Query $query)
    {
        $this->_query = $query;
        $sql = '';

        if ($query->type == Query::$UPDATE) {
            $sql .= $this->update();
            $sql .= $this->where();

        } else if ($query->type == Query::$INSERT) {
            $sql .= $this->insert();

        } else if ($query->type == Query::$DELETE) {
            $sql .= $this->delete();
            $sql .= $this->from();
            $sql .= $this->join();
            $sql .= $this->where();

        } else if ($query->type == Query::$COUNT) {
            $sql .= $this->select();
            $sql .= $this->from();
            $sql .= $this->join();
            $sql .= $this->where();

        }
        else if ($query->type == Query::$DESCRIBE) {
            $sql .= $this->describe();
        }
        else {
            $sql .= $this->select();
            $sql .= $this->from();
            $sql .= $this->join();
            $sql .= $this->where();
            $sql .= $this->groupBy();
            $sql .= $this->order();
            $sql .= $this->limit();
            $sql .= $this->offset();
        }
        return rtrim($sql);
    }

    public function getBindings()
    {
        return $this->_bindings;
    }

    public function getExceptionForError($errorInfo)
    {
        if ($this->isConnectionError($errorInfo)) {
            return '\Ouzo\DbConnectionException';
        }
        return '\Ouzo\DbException';
    }

    public function isConnectionError($errorInfo)
    {
        return in_array($this->getErrorCode($errorInfo), $this->getConnectionErrorCodes());
    }

    abstract public function getConnectionErrorCodes();

    abstract public function getErrorCode($errorInfo);
}