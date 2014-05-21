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
    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    /*
     * This is the ID field in the database for the object. This is stored so
     * we know what the primary key for the table is for each model.
     */
    protected $_idField;

    /*
     * Name of the model's table in the database. This is set automatically
     * unless overridden.
     */
    protected $_tableName;

    /*
     * Name of the current instance's model. Used for automatically creating
     * new instances when returning objects.
     */
    protected $_className;

    /*
     * Schema variable is built from the table in the database for the Model.
     * This is used to determine what values to set, default values, and what
     * to insert and update in the database when the save() method is called.
     */
    protected static $_schema = array();

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

    /*
     * Array of variables to pass to PDO to bind in preparing DB queries
     */
    protected static $bindings = array();

    /**
     * creates a PDO database connection when a model is constructed
     * We are using the try/catch error/exception handling here
     */
    public function __construct($params = array())
    {
        $this->_idField = static::getIdField();
        $this->_tableName = static::getTableName();
        $this->_className = static::getClassName();

        try {
            self::$db = new Database();
        } catch (PDOException $e) {
            die('Database connection could not be established.');
        }

        static::getSchema();
        if (!empty($params)) {
            $this->set($params);
        }
    }

    public static function getIdField()
    {
        return 'id';
    }

    public static function getClassName()
    {
        return strtolower(get_called_class());
    }

    public static function getTableName()
    {
        return Inflector::pluralize(strtolower(get_called_class()));
    }

    public static function getSchema()
    {
        $className = static::getClassName();
        $tableName = static::getTableName();
        if (!isset(static::$_schema[$className])) {
            $query = "DESCRIBE {$tableName};";
            $sth = self::$db->prepare($query);
            $sth->execute(self::$bindings);
            $fields = $sth->fetchAll();

            foreach ($fields as $info) {
                static::$_schema[$className][$info->Field] = array(
                    'type' => $info->Type,
                    'null' => $info->Null,
                    'key' => $info->Key,
                    'default' => $info->Default,
                    'extra' => $info->Extra
                );
            }
        }
        return static::$_schema[$className];
    }

    public function __call($name, $arguments)
    {
        /*
         * Build magic 'findByFIELD' functions
         */
        if (preg_match('#\Afind.*By(.+)$#', $name, $matches)) {
            $field = strtolower($matches[1]);
            $params = array(
                'conditions' => array(
                    $field => $arguments[0]
                )
            );
            if (preg_match("#\\AfindFirstBy{$matches[1]}$#", $matches[0])) {
                $params['limit'] = 1;
                return $this->findFirst($params);
            }

            if (array_key_exists($field, static::getSchema())) {
                return $this->find($params);
            }
        }
    }

    /**
     * This is the master find function for all models. All find functions
     * are essentially calling this function with certain parameters set.
     * Ex: findCount is this function with count set to TRUE automatically.
     *
     * @param array $params
     *
     * @return array
     */
    public function find($params = array())
    {
        self::$bindings = array();
        $return_objects = true;

        $where = '';
        if (isset($params['conditions'])) {
            $where = 'WHERE ' . $this->_buildFindConditions($params['conditions']);
        }

        $fields = '*';
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

        $sth = self::$db->prepare($query);
        $sth->execute(self::$bindings);

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
                    $retval[] = "$k :$k" . sizeof(self::$bindings) . "";
                }
                else {
                    $retval[] = "$k = :$k" . sizeof(self::$bindings) . "";
                }
                self::$bindings[":$k" . sizeof(self::$bindings)] = $v;
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
        foreach (static::getSchema() as $variable => $info) {
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
        if (!isset($this->{$this->_idField})) {
            $this->{$this->_idField} = null;
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
            if (array_key_exists($variable, static::getSchema())) {
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

        self::$bindings = array();
        $columns = array();
        $set = array();

        foreach ($this as $col => $val) {
            if ($col == 'created'  || $col == 'modified') {
                continue;
            }
            else if (array_key_exists($col, static::getSchema())) {
                self::$bindings[":$col"] = $val;

                // @TODO: try and get rid of $set and $columns by using array walk. i.e. - this might not be any more efficient.
                // Columns are used for INSERT
                $columns[] = $col;
                // Set array is used for UPDATE
                $set[] = "$col = :$col";
            }
        }

        self::$db->beginTransaction();

        // If ID is not null, then UPDATE row in the database, else INSERT new row
        if ($this->{"{$this->_idField}"} !== null) {
            // Update query
            $set[] = "modified = :modified";

            $query = "UPDATE {$this->_tableName} SET " . implode(', ', $set) . " WHERE {$this->_idField} = :id_val";
            self::$bindings[':id_val'] = $this->{"{$this->_idField}"};
            self::$bindings[':modified'] = date("Y-m-d H:i:s");

            $sth = self::$db->prepare($query);
            $success = $sth->execute(self::$bindings);
        }
        else {
            // Insert query
            $columns[] = 'created';
            self::$bindings[':created'] = date("Y-m-d H:i:s");

            $query = "INSERT INTO {$this->_tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_keys(self::$bindings)) . ")";

            $sth = self::$db->prepare($query);

            $success = $sth->execute(self::$bindings);
            $this->{"{$this->_idField}"} = self::$db->lastInsertId();
        }

        if ($this->afterSave() == false) {
            self::$db->rollBack();
            return false;
        }

        self::$db->commit();
        return $success;
    }

    // @TODO: move validating to its own class
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
                    $this->Session->setFlash($message, 'failure');
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
                                if ($result->{$this->_idField} != $this->{$this->_idField}) {
                                    $this->Session->setFlash($message, 'failure');
                                    return false;
                                }
                            }
                        }
                        break;
                    case 'email':
                        if (!filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                            $this->Session->setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate alpha-numeric field
                    case 'alphaNumeric':
                        if (!ctype_alnum($this->$field)) {
                            $this->Session->setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate numeric field
                    case 'numeric':
                        if (!is_numeric($this->$field)) {
                            $this->Session->setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate max length
                    case 'max_length':
                        if (strlen($this->$field) > $info['size']) {
                            $this->Session->setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate min length
                    case 'min_length':
                        if (strlen($this->$field) < $info['size']) {
                            $this->Session->setFlash($message, 'failure');
                            return false;
                        }
                        break;
                    // Validate custom regex
                    case 'regex':
                        if (!preg_match($info['rule'], $this->$field)) {
                            $this->Session->setFlash($message, 'failure');
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
        $sth = self::$db->prepare("DELETE FROM {$this->_tableName} WHERE {$this->_idField} = :id;");
        $success = $sth->execute(array(
            ':id' => $this->{$this->_idField},
        ));

        return $success;
    }

    // @TODO: should make these functions static
    public function deleteById($id)
    {
        $sth = self::$db->prepare("DELETE FROM {$this->_tableName} WHERE {$this->_idField} = :id;");
        $success = $sth->execute(array(
            ':id' => $id,
        ));

        return $success;
    }

    public function findById($id)
    {
        $sth = self::$db->prepare("SELECT * FROM {$this->_tableName} WHERE {$this->_idField} = :id;");
        $sth->execute(array(
            ':id' => $id,
        ));

        $o = new $this($sth->fetch());
        return $o;
    }

}