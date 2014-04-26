<?php
/**
 * @author Alex Phillips
 * Date: 4/5/14
 * Time: 11:48 AM
 */

$backup = new backup_database(array(
    'host'     => '127.0.0.1',
    'user'     => 'root',
    'password' => 'applepie',
    'db'       => 'primer',
));
$backup->backup();

class backup_database
{
    private $_host;
    private $_user;
    private $_password;
    private $_db;
    private $_connection;

    public function __construct($config)
    {
        $this->_host = $config['host'];
        $this->_user = $config['user'];
        $this->_password = $config['password'];
        $this->_db = $config['db'];

        $this->_connection = new PDO("mysql:host={$this->_host};dbname={$this->_db}", $this->_user, $this->_password);
    }

    public function backup()
    {
        $output = <<<__TEXT__
--
-- Database: `{$this->_db}`
--

-- --------------------------------------------------------

__TEXT__;
        $query = $this->_connection->prepare('SHOW TABLES');
        $query->execute();

        foreach ($query->fetchAll() as $result) {
            $table = $result["Tables_in_{$this->_db}"];
            $createTable = $this->getCreateTable($table);
            $output .= <<<__TEXT__

--
-- Table structure for table `$table`
--

$createTable

__TEXT__;

        }

        echo $output;
    }

    private function getCreateTable($table)
    {
        $query = $this->_connection->prepare('SHOW CREATE TABLE ' . $table);
        $query->execute();
        $result = $query->fetch();
        return $result['Create Table'];
    }
}