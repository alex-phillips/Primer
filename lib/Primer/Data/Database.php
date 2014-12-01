<?php

namespace Primer\Data;

use PDO;
use PDOException;
use Primer\Proxy\Config;

class Database
{
    private $_dbHandle;
    private $_type;
    private static $_instance;

    protected function __construct($config)
    {
        switch (strtolower($config['db_type'])) {
            case 'mysql':
                // set the (optional) options of the PDO connection. in this case, we set the fetch mode to
                // "objects", which means all results will be objects, like this: $result->user_name !
                // For example, fetch mode FETCH_ASSOC would return results like this: $result["user_name] !
                // @see http://www.php.net/manual/en/pdostatement.fetch.php
                $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ);

                // generate a database connection, using the PDO connector
                // @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
                try {
                    $this->_dbHandle = new PDO(
                        $config['db_type'] . ':host=' . $config['host'] . ';dbname=' . $config['database'],
                        $config['login'],
                        $config['password'],
                        $options
                    );
                } catch (PDOException $e) {
                    die('Database connection could not be established.');
                }
                break;
            case 'sqlite3':
                try {
                    /*** connect to SQLite database ***/
                    if (!isset($config['path'])) {
                        die('Database connection could not be established.');
                    }

                    $this->_dbHandle = new PDO("sqlite:" . $config['path']);
                } catch(PDOException $e) {
                    die('Database connection could not be established.');
                }
                break;
            case 'postgres':
                break;
        }

        $this->_type = $config['db_type'];

        $this->_dbHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        self::$_instance = $this;
    }

    public static function getInstance()
    {
        if (self::$_instance) {
            return self::$_instance;
        }

        return new self(Config::get('database'));
    }

    public function getType()
    {
        return $this->_type;
    }

    public function beginTransaction()
    {
        $this->_dbHandle->beginTransaction();
    }

    public function commit()
    {
        $this->_dbHandle->commit();
    }

    public function inTransaction()
    {
        return $this->_dbHandle->inTransaction();
    }

    public function prepare($query)
    {
        return $this->_dbHandle->prepare($query);
    }

    public function execute(&$statement, $bindings = null)
    {
        return $statement->execute($bindings);
    }

    public function lastInsertId()
    {
        return $this->_dbHandle->lastInsertId();
    }
}