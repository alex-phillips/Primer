<?php

class Database extends PDO
{
    public function __construct()
    {
        $config = new DATABASE_CONFIG();
        $config = $config->{ENVIRONMENT};

        if (strcasecmp($config['db_type'], 'mysql') === 0) {
            // set the (optional) options of the PDO connection. in this case, we set the fetch mode to
            // "objects", which means all results will be objects, like this: $result->user_name !
            // For example, fetch mode FETCH_ASSOC would return results like this: $result["user_name] !
            // @see http://www.php.net/manual/en/pdostatement.fetch.php
            $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ);

            // generate a database connection, using the PDO connector
            // @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
            parent::__construct($config['db_type'] . ':host=' . $config['host'] . ';dbname=' . $config['database'], $config['login'], $config['password'], $options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
}