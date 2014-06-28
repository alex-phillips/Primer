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
    // PROPERTIES, PUBLIC
    /////////////////////////////////////////////////

    /*
     * Array that contains validation and save error messages
     */
    public $errors = array();

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
     * new instances when returning objects. Ex: user, post
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
    protected static $_validate = array();

    /*
     * Database variable to handle all query creations and executions.
     */
    protected static $_db;

    /*
     * Array of variables to pass to PDO to bind in preparing DB queries
     */
    protected static $_bindings = array();

    /**
     * creates a PDO database connection when a model is constructed
     * We are using the try/catch error/exception handling here
     */
    public function __construct($params = array())
    {
        $this->_idField = static::getIdField();
        $this->_tableName = static::getTableName();
        $this->_className = static::getClassName();

        self::init();

        static::getSchema();
        if (!empty($params)) {
            $this->set($params);
        }
    }

    /**
     * Function to initialize the parent model class that all models
     * inherit from. Anything that should be executed for all models
     * shbould be added here whether or not a model will be automatically
     * loaded or not.
     */
    public static function init()
    {
        if (!self::$_db) {
            try {
                self::$_db = new Database();
            } catch (PDOException $e) {
                die('Database connection could not be established.');
            }
        }
    }

    /**
     * Returns the default database ID field for the Model
     *
     * @return string
     */
    public static function getIdField()
    {
        return 'id';
    }

    /**
     * Returns the default foreign key for a class
     * Ex: user's ID in post table = id_user
     *
     * @param $class
     *
     * @return string
     */
    public static function getForeignIdField($class)
    {
        return 'id_' . self::getClassName($class);
    }

    /**
     * Getter function for retrieving a model's name
     * Ex: User model returns 'user'
     *
     * @param null $class
     *
     * @return string
     */
    public static function getClassName($class = null)
    {
        if (!$class) {
            return strtolower(get_called_class());
        }
        return strtolower(Inflector::singularize($class));
    }

    /**
     * Returns the default table name for a model
     * Ex: User model returns 'users'
     *
     * @return string
     */
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
            $sth = self::$_db->prepare($query);
            $sth->execute(self::$_bindings);
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

    /**
     * Getter function for retrieving the models validation
     * data structure.
     *
     * @return array
     */
    public static function getValidationArray()
    {
        return static::$_validate;
    }

    /**
     * Overloaded instance functions
     *
     * @param $name
     * @param $arguments
     *
     * @return array|null
     */
    public function __call($name, $arguments)
    {
        /*
         * Build magic 'findByFIELD' functions
         */
        if (preg_match('#\Afind.*By(.+)$#', $name, $matches)) {
            return static::__callStatic($name, $arguments);
        }

        /*
         * Build magic 'get' functions to get another set of models
         * from the database by foreign ID.
         * Ex:  $user->getPosts() - returns posts with id_user set to
         *      the ID of the user object making the call
         */
        if (preg_match('#\Aget(.+)$#', $name, $matches)) {
            return $this->getRelatedModels($matches[1]);
        }
    }

    /**
     * Overloaded static functions
     *
     * @param $name
     * @param $arguments
     *
     * @return array|null
     */
    public static function __callStatic($name, $arguments)
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
            if (!preg_match("#\\AfindAllBy{$matches[1]}$#", $matches[0])) {
                $params['limit'] = 1;
                return static::findFirst($params);
            }

            if (array_key_exists($field, static::getSchema())) {
                return static::find($params);
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
    public static function find($params = array())
    {
        $className = static::getClassName();
        $tableName = static::getTableName();
        self::$_bindings = array();
        $return_objects = true;

        $where = '';
        if (isset($params['conditions'])) {
            $where = 'WHERE ' . static::_buildFindConditions($params['conditions']);
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

        $query = "SELECT $fields FROM {$tableName} $where $order $limit $offset;";

        $sth = self::$_db->prepare($query);
        $sth->execute(self::$_bindings);

        $results = array();
        foreach ($sth->fetchAll() as $result) {
            if ($return_objects) {
                $results[] = new $className($result);
            }
            else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * This is a recursive function that will traverse an array to build the find
     * conditions for a query.
     *
     * @param $conditions
     * @param string $conjunction
     *
     * @return string
     */
    protected static function _buildFindConditions($conditions, $conjunction = '')
    {
        $retval = array();
        foreach ($conditions as $k => $v) {
            if ((strtoupper($k) === 'OR' || strtoupper($k) === 'AND') && is_array($v)) {
                $retval[] = '(' . static::_buildFindConditions($v, strtoupper($k)) . ')';
            }
            else if (is_array($v)) {
                $retval[] = static::_buildFindConditions($v);
            }
            else {
                if (preg_match('# LIKE$#', $k)) {
                    $retval[] = "$k :$k" . sizeof(self::$_bindings) . "";
                }
                else {
                    $retval[] = "$k = :$k" . sizeof(self::$_bindings) . "";
                }
                self::$_bindings[":$k" . sizeof(self::$_bindings)] = $v;
            }
        }
        return implode(" $conjunction ", $retval);
    }

    public static function findFirst($params = array())
    {
        $params['limit'] = 1;
        $results = static::find($params);
        return (!empty($results)) ? $results[0] : null;
    }

    public static function findCount($params = array())
    {
        $params['count'] = true;
        $results = static::find($params);
        return $results[0]->{"COUNT(*)"};
    }

    public function getRelatedModels($relatedModel)
    {
        $retval = array();
        $relatedModel = Primer::getModelName($relatedModel);

        if (array_key_exists($this->getForeignIdField($this->_className), $relatedModel::getSchema())) {
            $retval = call_user_func(array($relatedModel, 'find'), array(
                'conditions' => array(
                    $this->getForeignIdField($this->_className) => $this->id
                )
            ));
        }

        return $retval;
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

        $this->validate();
        if (!empty($this->errors)) {
            return false;
        }

        if (!$this->verifyRelationships()) {
            return false;
        }

        self::$_bindings = array();
        $columns = array();
        $set = array();

        foreach ($this as $col => $val) {
            if ($col == 'created'  || $col == 'modified') {
                continue;
            }
            else if (array_key_exists($col, static::getSchema())) {
                self::$_bindings[":$col"] = $val;

                // @TODO: try and get rid of $set and $columns by using array walk. i.e. - this might not be any more efficient.
                // Columns are used for INSERT
                $columns[] = $col;
                // Set array is used for UPDATE
                $set[] = "$col = :$col";
            }
        }

        self::$_db->beginTransaction();

        // If ID is not null, then UPDATE row in the database, else INSERT new row
        if ($this->{"{$this->_idField}"} !== null) {
            // Update query
            $set[] = "modified = :modified";

            $query = "UPDATE {$this->_tableName} SET " . implode(', ', $set) . " WHERE {$this->_idField} = :id_val";
            self::$_bindings[':id_val'] = $this->{"{$this->_idField}"};
            self::$_bindings[':modified'] = date("Y-m-d H:i:s");

            $sth = self::$_db->prepare($query);
            $success = $sth->execute(self::$_bindings);
        }
        else {
            // Insert query
            $columns[] = 'created';
            self::$_bindings[':created'] = date("Y-m-d H:i:s");

            $query = "INSERT INTO {$this->_tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_keys(self::$_bindings)) . ")";

            $sth = self::$_db->prepare($query);

            $success = $sth->execute(self::$_bindings);
            $this->{"{$this->_idField}"} = self::$_db->lastInsertId();
        }

        if ($this->afterSave() == false) {
            self::$_db->rollBack();
            return false;
        }

        self::$_db->commit();
        return $success;
    }

    private function verifyRelationships()
    {
        /*
         * Verify 'belongs to' relationships
         */
        if (isset($this->belongsTo)) {
            if (is_array($this->belongsTo)) {

            }
            else if (is_string($this->belongsTo)) {
                $ownerModel = Primer::getModelName($this->belongsTo);
                try {
                    $ownerId = $this->{$this->getForeignIdField($this->belongsTo)};
                    $owner = call_user_func(array($ownerModel, 'findById'), $ownerId);
                    if (!($owner instanceof $ownerModel)) {
                        return false;
                    }
                }
                catch (Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

    // @TODO: move validating to its own class
    private function validate($rules = null)
    {
        if (!$rules) {
            $rules = static::$_validate;
        }

        foreach ($rules as $field => $rules) {
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
                    $this->errors[] = $message;
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
                    case 'unique':
                        $results = $this->find(array(
                            'conditions' => array(
                                $field => $this->$field
                            )
                        ));
                        if (!empty($results)) {
                            foreach ($results as $result) {
                                if ($result->{$this->_idField} != $this->{$this->_idField}) {
                                    $this->errors[] = $message;
                                }
                            }
                        }
                        break;
                    // Validate e-mail
                    case 'email':
                        if (!filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate alpha-numeric field
                    case 'alphaNumeric':
                        if (!ctype_alnum($this->$field)) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate numeric field
                    case 'numeric':
                        if (!is_numeric($this->$field)) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate max length
                    case 'max_length':
                        if (strlen($this->$field) > $info['size']) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate min length
                    case 'min_length':
                        if (strlen($this->$field) < $info['size']) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate list of options
                    case 'in_list':
                        if (!in_array($this->$field, $info['list'])) {
                            $this->errors[] = $message;
                        }
                        break;
                    // Validate custom regex
                    case 'regex':
                        if (!preg_match($info['rule'], $this->$field)) {
                            $this->errors[] = $message;
                        }
                        break;
                }
            }
        }
    }

    public function delete()
    {
        $sth = self::$_db->prepare("DELETE FROM {$this->_tableName} WHERE {$this->_idField} = :id;");
        $success = $sth->execute(array(
            ':id' => $this->{$this->_idField},
        ));

        unset($this);
        return $success;
    }

    public static function deleteById($id)
    {
        $idField = static::getIdField();
        $tableName = static::getTableName();
        $sth = self::$_db->prepare("DELETE FROM {$tableName} WHERE {$idField} = :id;");
        $success = $sth->execute(array(
            ':id' => $id,
        ));

        return $success;
    }

    /**
     * Return a JSON serialized instance of the model as it would exists
     * in the database.
     *
     * @return string
     */
    public function JSONSerialize()
    {
        $retval = new stdClass();
        foreach ($this as $key => $value) {
            if (array_key_exists($key, $this->getSchema())) {
                $retval->$key = $value;
            }
        }
        return json_encode($retval);
    }
}