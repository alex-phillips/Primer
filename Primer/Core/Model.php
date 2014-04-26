<?php

/**
 * Class Model
 * @author Alex Phillips
 *
 * Class in which all models are inherited from. Contains all 'generic' database
 * interactions and validation for models.
 */
class Model
{
    /*
     * Schema variable is built from the table in the database for the Model.
     * This is used to determine what values to set, default values, and what
     * to insert and update in the database when the save() method is called.
     */
    protected static $_schema;

    /*
     * This is the ID field in the database for the object. This is stored so
     * we know what the primary key for the table is for each model.
     */
    protected $_id_field;

    /*
     * Created variable that every model will have. This is automatically
     * created *once* when a row is created in the database.
     */
    public $created;

    /*
     * Modified variable that every model will have. This is automatically
     * updated when a row is altered in the database.
     */
    public $modified;

    /*
     * Validation array contains rules to check on each model field.
     * This is not validation for forms or clientside validation, but
     * validation before a model is created or updated in the database.
     */
    protected static $validate = array();

    /*
     * Database variable to handle all query creations and executions.
     */
    protected static $db;
    protected static $bindings = array();

    /**
     * creates a PDO database connection when a model is constructed
     * We are using the try/catch error/exception handling here
     */
    public function __construct($params = array())
    {
        $this->_tableName = Inflector::pluralize(strtolower(get_class($this)));
        $this->_className = strtolower(get_class($this));
        $this->_id_field = "id_{$this->_className}";

        try {
            static::$db = new Database();
            static::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection could not be established.');
        }

        $this->getSchema();
        if (!empty($params)) {
            $this->set($params);
        }
    }

    public function getSchema()
    {
        if (!static::$_schema) {
            $query = "DESCRIBE {$this->_tableName};";
            $sth = static::$db->prepare($query);
            $sth->execute(static::$bindings);
            $fields = $sth->fetchAll();

            foreach ($fields as $info) {
                static::$_schema[$info->Field] = array(
                    'type' => $info->Type,
                    'null' => $info->Null,
                    'key' => $info->Key,
                    'default' => $info->Default,
                    'extra' => $info->Extra
                );
                if ($info->Key === 'PRI') {
                    $this->_id_field = $info->Field;
                }
            }
        }
        return static::$_schema;
    }

    public function find($params = array())
    {
        static::$bindings = array();
        $return_objects = true;
        $bindings = array();

        $where = '';
        if (isset($params['conditions'])) {
            $where = 'WHERE ' . $this->_buildFindConditions($params['conditions']);
        }

        $fields = '*';
        $count = '';
        if (isset($params['fields']) && is_array($params['fields'])) {
            $fields = implode(', ', $params['fields']);
        }
        else if (isset($params['fields']) && is_string($params['fields'])) {
            $fields = $params['fields'];
        }

        if (isset($params['count']) && $params['count'] === true) {
            if ($fields === '*' || is_string($params['fields'])) {
                $fields = 'COUNT(' . $fields . ')';
                $return_objects = false;
            }
        }

        $order = '';
        if (isset($params['order'])) {
            $order = 'ORDER BY ' . implode(', ', $params['order']);
        }

        $limit = '';
        if (isset($params['limit'])) {
            $limit = 'LIMIT ' . $params['limit'];
        }

        $offset = '';
        if (isset($params['offset'])) {
            $offset = 'OFFSET ' . $params['offset'];
        }

        $query = "SELECT $fields FROM {$this->_tableName} $where $order $limit $offset;";

        $sth = static::$db->prepare($query);
        $sth->execute(static::$bindings);

        $results = array();
        foreach ($sth->fetchAll() as $result) {
            if ($return_objects) {
                $results[] = new $this->_className($result);
            }
            else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * This is a recursive function that will traverse an array to build the find
     * conditions for a query much like CakePHP's method to build queries through
     * its find() function.
     *
     * @param $conditions
     * @param string $conjunction
     *
     * @return string
     */
    protected function _buildFindConditions($conditions, $conjunction = '')
    {
        $retval = array();
        foreach ($conditions as $k => $v) {
            if ((strtoupper($k) === 'OR' || strtoupper($k) === 'AND') && is_array($v)) {
                $retval[] = '(' . $this->_buildFindConditions($v, strtoupper($k)) . ')';
            }
            else if (is_array($v)) {
                $retval[] = $this->buildConditions($v);
            }
            else {
                if (preg_match('# LIKE$#', $k)) {
                    $retval[] = "$k :$k" . sizeof(static::$bindings) . "";
                }
                else {
                    $retval[] = "$k = :$k" . sizeof(static::$bindings) . "";
                }
                static::$bindings[":$k" . sizeof(static::$bindings)] = $v;
            }
        }
        return implode(" $conjunction ", $retval);
    }

    public function findFirst($params = array())
    {
        $params['limit'] = 1;
        $results = $this->find($params);
        return (!empty($results)) ? $results[0] : null;
    }

    public function findCount($params = array())
    {
        $params['count'] = true;
        $results = $this->find($params);
        return $results[0]->{"COUNT(*)"};
    }

    /**
     * This is a setter for the entirety of the model. It is called
     * once in the constructor if parameters are passed in. This will
     * only set variables that exist in the model.
     * You can create an array and, at any point, pass it into the set
     * function to update/override any existing variables.
     *
     * @param $params
     */
    public function set($params)
    {
        $params = (array)$params;
        foreach ($this->getSchema() as $variable => $info) {
            // Instantiate variable if a value was given for it
            if (isset($params[$variable])) {
                $this->$variable = $params[$variable];
            }
            // Only set other variables to NULL if a that is accepted. Otherwise,
            // a value should be provided before save.
            else if ($info['default'] === null && $info['null'] === 'YES') {
                $this->$variable = null;
            }
        }

        // Always instantiate the ID field even though it is requried
        if (!isset($this->{$this->_id_field})) {
            $this->{$this->_id_field} = null;
        }
    }

    /**
     * This is a function designed to update the values of an object while
     * not replacing any properties that aren't included in the passed in
     * parameters. Use set to 'reinstantiate' the object only with given params.
     *
     * @param $params
     */
    public function update($params)
    {
        $params = (array)$params;
        foreach ($params as $variable => $value) {
            if (array_key_exists($variable, $this->getSchema())) {
                $this->$variable = $value;
            }
        }
    }

    /**
     * Function that can be overriden to perform any actions that need
     * to be done before the model is saved or udpated in the database.
     * This will return true on success, and false on failure. The save
     * function will only continue if beforeSave returns true.
     *
     * @return bool
     */
    protected function beforeSave()
    {
        return true;
    }

    /**
     * Function that can be overriden to perform any actions after the model
     * has been saved or updated in the database.
     *
     * @return bool
     */
    protected function afterSave()
    {
        return true;
    }

    /**
     * Abstract save function to handle INSERT and UPDATE queries into
     * the database. This function will call beforeSave(). If it fails,
     * it will return false. It will then call validate() which, if it
     * fails, will return false. Data structures are built based on the
     * object's class variables. If the object's ID field is null, the
     * function will run an INSERT query, otherwise it will run an
     * UPDATE query. Afterwards, the afterSave() function is called.
     * If this returns true, the query is committed and the function
     * returns true. Otherwise, it is the query is rolled back and the
     * function returns false.
     *
     * @return bool true on success, false on failure
     */
    public function save()
    {
        if ($this->beforeSave() == false) {
            return false;
        }

        if (!$this->validate()) {
            return false;
        }

        $key_bindings = array();
        $columns = array();
        $values = array();
        $set = array();

        foreach ($this as $col => $val) {
            if ($col == 'created'  || $col == 'modified') {
                continue;
            }
            else if (array_key_exists($col, static::$_schema)) {
                $key_bindings[":$col"] = $val;

                // Columns and Values are used for Insert
                $columns[] = $col;
                $values[] = ":$col";

                // Set array is used for updating
                $set[] = "$col = :$col";
            }
        }

        static::$db->beginTransaction();

        if ($this->{"{$this->_id_field}"} != null) {
            // Update query
            $set[] = "modified = :modified";

            $query = "UPDATE {$this->_tableName} SET " . implode(', ', $set) . " WHERE {$this->_id_field} = :id_val";
            $key_bindings[':id_val'] = $this->{"{$this->_id_field}"};
            $key_bindings[':modified'] = date("Y-m-d H:i:s");

            $sth = static::$db->prepare($query);
            $success = $sth->execute($key_bindings);
        }
        else {
            // Insert query
            $columns[] = 'created';
            $values[] = ':created';

            $query = "INSERT INTO {$this->_tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
            $key_bindings[':created'] = date("Y-m-d H:i:s");

            $sth = static::$db->prepare($query);

            $success = $sth->execute($key_bindings);
            $this->{"{$this->_id_field}"} = static::$db->lastInsertId();
        }

        if ($this->afterSave() == false) {
            static::$db->rollBack();
            return false;
        }

        static::$db->commit();
        return $success;
    }

    private function validate()
    {
        foreach (static::$validate as $field => $rules) {

            // FIRST, test if field is required
            if ($this->$field == '') {
                // If not required and
                if (!array_key_exists('required', $rules)) {
                    break;
                } else {
                    if (!isset($rules['required']['message'])) {
                        $message = 'There was a problem validating REQUIRED for field ' . strtoupper($field);
                    }
                    else {
                        $message = $rules['required']['message'];
                    }
                    Session::setFlash($message, 'failure');
                    return false;
                }
            }

            foreach ($rules as $rule => $info) {
                $message = 'There was a problem rule name ' . strtoupper($rule) . ' for field ' . strtoupper($field);
                if (isset($info['message'])) {
                    $message = $info['message'];
                }

                if (!$this->$field) {
                    continue;
                }

                switch ($rule) {
                    // Validate e-mail
                    case 'unique':
                        $results = $this->find(array(
                            'conditions' => array(
                                $field => $this->$field
                            )
                        ));
                        if (!empty($results)) {
                            foreach ($results as $result) {
                                if ($result->{$this->_id_field} != $this->{$this->_id_field}) {
                                    Session::setFlash($message, 'failure');
                                    return false;
                                }
                            }
                        }
                        break;
                    case 'email':
                        if (!filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate alpha-numeric field
                    case 'alphaNumeric':
                        if (!ctype_alnum($this->$field)) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate numeric field
                    case 'numeric':
                        if (!is_numeric($this->$field)) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate max length
                    case 'max_length':
                        if (strlen($this->$field) > $info['size']) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate min length
                    case 'min_length':
                        if (strlen($this->$field) < $info['size']) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate custom regex
                    case 'regex':
                        if (!preg_match($info['rule'], $this->$field)) {
                            Session::setFlash($message, 'failure');
                            return false;
                        }
                        break;
                }
            }
        }
        return true;
    }

    public function delete()
    {
        $sth = static::$db->prepare("DELETE FROM {$this->_tableName} WHERE {$this->_id_field} = :id;");
        $success = $sth->execute(array(
            ':id' => $this->{$this->_id_field},
        ));

        return $success;
    }

    public function deleteById($id)
    {
        $sth = static::$db->prepare("DELETE FROM {$this->_tableName} WHERE {$this->_id_field} = :id;");
        $success = $sth->execute(array(
            ':id' => $id,
        ));

        return $success;
    }

    public function findById($id)
    {
        $sth = static::$db->prepare("SELECT * FROM {$this->_tableName} WHERE {$this->_id_field} = :id;");
        $sth->execute(array(
            ':id' => $id,
        ));

        $o = new $this($sth->fetch());
        return $o;
    }

}