<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/19/14
 * Time: 5:22 PM
 */

namespace Primer\Data;

use PDO;

class Query
{
    public $table;
    public $aliasTable;
    public $distinct = false;
    public $selectColumns;
    public $selectType = PDO::FETCH_ASSOC;
    public $order;
    public $limit;
    public $offset;
    public $updateAttributes = array();
    public $whereClauses = array();
    public $joinClauses = array();
    public $type;
    public $options = array();
    public $groupBy;

    public static $SELECT = 1;
    public static $COUNT = 2;
    public static $DELETE = 3;
    public static $UPDATE = 4;
    public static $INSERT = 5;
    public static $DESCRIBE = 6;

    public function __construct($type = null)
    {
        $this->type = $type ? $type : Query::$SELECT;
    }

    public static function newInstance($type = null)
    {
        return new Query($type);
    }

    public static function insert($attributes)
    {
        return Query::newInstance(Query::$INSERT)->attributes($attributes);
    }

    public static function update($attributes)
    {
        return Query::newInstance(Query::$UPDATE)->attributes($attributes);
    }

    public static function select($selectColumns = null)
    {
        $query = new Query();
        $query->selectColumns = $selectColumns;
        return $query;
    }

    public static function selectDistinct($selectColumns = null)
    {
        $query = self::select($selectColumns);
        $query->distinct = true;
        return $query;
    }

    public static function count()
    {
        return new Query(Query::$COUNT);
    }

    public static function delete()
    {
        return new Query(Query::$DELETE);
    }

    public function attributes($attributes)
    {
        $this->updateAttributes = $attributes;
        return $this;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function into($table)
    {
        return $this->table($table);
    }

    public function from($table)
    {
        return $this->table($table);
    }

    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function join($joinTable, $joinKey, $idName, $alias = null, $type = 'LEFT', $onClauses = array())
    {
        $this->joinClauses[] = new JoinClause($joinTable, $joinKey, $idName, $this->aliasTable ? : $this->table, $alias, $type, $onClauses);
        return $this;
    }

    public function addJoin(JoinClause $join)
    {
        $this->joinClauses[] = $join;
        return $this;
    }

    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }
}