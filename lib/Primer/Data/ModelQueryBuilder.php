<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 11/19/14
 * Time: 5:44 PM
 */

namespace Primer\Data;

use PDO;
use Primer\Data\Dialect\MySQL;
use Primer\Data\Dialect\Postgres;
use Primer\Data\Dialect\Sqlite3;

class ModelQueryBuilder
{
    private $_database;
    private $_adapter;
    private $_statement;
    private $_fetchStyle = PDO::FETCH_OBJ;

    public function __construct(Model $model, $database = null, $alias = null)
    {
        $this->_database = $database ? $database : Database::getInstance();

        switch (strtolower($this->_database->getType())) {
            case 'mysql':
                $this->_adapter = new MySQL();
                break;
            case 'postgres':
                $this->_adapter = new Postgres();
                break;
            case 'sqlite3':
            default:
                $this->_adapter = new Sqlite3();
                break;
        }

        $this->_model = $model;

        $this->_query = new Query();
        $this->_query->table = $model->getTableName();
        $this->_query->aliasTable = $alias ?: $model::getModelName();
        $this->_query->selectType = PDO::FETCH_NUM;
        $this->_query->selectColumns = array();
    }

    public function query($query, $bindings = array())
    {
//        $this->_database->prepare($query);
//
//        return $this->_database->execute($bindings);
    }

    public function select($fields)
    {
        if ($fields) {
            if (!is_array($fields)) {
                $fields = array($fields);
            }

            $this->_query->selectColumns = $fields;
        }

        return $this;
    }

    public function where($conditions)
    {
        $this->_query->whereClauses = $conditions;

        return $this;
    }

    public function limit($limit)
    {
        $this->_query->limit = $limit;

        return $this;
    }

    public function count()
    {
        $this->_query->type = Query::$COUNT;
        $this->_fetchStyle = PDO::FETCH_NUM;

        return $this;
    }

    public function delete()
    {
        $this->_query->type = Query::$DELETE;

        return $this;
    }

    public function describe($tableName)
    {
        $this->_query->type = Query::$DESCRIBE;
        $this->_query->table = $tableName;

        return $this;
    }

    public function update($attributes)
    {
        $this->_query->type = Query::$UPDATE;
        $this->_query->updateAttributes = $attributes;
        $this->_query->aliasTable = '';

        return $this;
    }

    public function insert($attributes)
    {
        $this->_query->type = Query::$INSERT;
        $this->_query->updateAttributes = $attributes;
        $this->_query->aliasTable = '';

        return $this;
    }

    public function getLastInsertId()
    {
        return $this->_database->lastInsertId();
    }

    public function execute()
    {
        $this->_statement = $this->_database->prepare($this->_adapter->buildQuery($this->_query), $this->_adapter->getBindings());

        return $this->_database->execute($this->_statement, $this->_adapter->getBindings());
    }

    public function executeAndFetch()
    {
        $this->_statement = $this->_database->prepare($this->_adapter->buildQuery($this->_query), $this->_adapter->getBindings());

        if ($this->_database->execute($this->_statement, $this->_adapter->getBindings())) {
            return $this->_statement->fetch($this->_fetchStyle);
        }

        return null;
    }

    public function executeAndFetchAll()
    {
        $this->_statement = $this->_database->prepare($this->_adapter->buildQuery($this->_query), $this->_adapter->getBindings());

        if ($this->_database->execute($this->_statement, $this->_adapter->getBindings())) {
            return $this->_statement->fetchAll($this->_fetchStyle);
        }

        return null;
    }

    public function fetch()
    {
        return $this->_statement->fetch($this->_fetchStyle);
    }

    public function fetchAll()
    {
        return $this->_statement->fetchAll($this->_fetchStyle);
    }
}