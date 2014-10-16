<?php

namespace Primer\Model;

use Carbon\Carbon;
use Primer\Core\Object;
use Primer\Utility\Inflector;
use Primer\Datasource\Database;

/**
 * Class Model
 * @author Alex Phillips
 *
 * Class in which all models are inherited from. Contains all 'generic' database
 * interactions and validation for models.
 */
abstract class Model extends Object
{
    /////////////////////////////////////////////////
    // PROPERTIES, PUBLIC
    /////////////////////////////////////////////////

    /*
     * Array that contains validation and save error messages
     */
    protected static $_schema = array();

    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    /*
     * This is the ID field in the database for the object. This is stored so
     * we know what the primary key for the table is for each model.
     */
    protected static $_validate = array();

    /*
     * Name of the model's table in the database. This is set automatically
     * unless overridden.
     */
    protected static $_db;

    /*
     * Name of the current instance's model. Used for automatically creating
     * new instances when returning objects. Ex: user, post
     */
    protected static $_bindings = array();

    /*
     * Schema variable is built from the table in the database for the Model.
     * This is used to determine what values to set, default values, and what
     * to insert and update in the database when the save() method is called.
     */
    public $errors = array();

    /*
     * Validation array contains rules to check on each model field.
     * This is not validation for forms or client-side validation, but
     * validation before a model is created or updated in the database.
     */
    protected $_idField;

    /*
     * Database variable to handle all query creations and executions.
     */
    protected $_tableName;

    /*
     * Array of variables to pass to PDO to bind in preparing DB queries
     */
    protected $_className;

    /**
     * creates a PDO database connection when a model is constructed
     * We are using the try/catch error/exception handling here
     */
    protected function __construct($params = array())
    {
        $this->_idField = static::getIdField();
        $this->_tableName = static::getTableName();
        $this->_className = static::getClassName();

        static::getSchema();
        if (!empty($params)) {
            $this->set($params);
        }
    }

    public static function create($params = array())
    {
        return new static($params);
    }

    public function __set($key, $value)
    {
        /*
         * Make sure that created and modified properties are Carbon objects.
         */
        if ($key === 'created' || $key === 'modified') {
            if ($value === null) {
                $this->$key = null;
            }
            else if ($value instanceof Carbon) {
                $this->$key = $value;
            }
            else {
                if (is_numeric($value)) {
                    $this->$key = Carbon::createFromTimestamp($value);
                }
                else {
                    $this->$key = Carbon::createFromTimestamp(strtotime($value));
                }
            }
        }
        else {
            $this->$key = $value;
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
     * Returns the default table name for a model
     * Ex: User model returns 'users'
     *
     * @return string
     */
    public static function getTableName()
    {
        return Inflector::pluralize(strtolower(get_called_class()));
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
     * Function to initialize the parent model class that all models
     * inherit from. Anything that should be executed for all models
     * shbould be added here whether or not a model will be automatically
     * loaded or not.
     */
    public static function init(Database $db)
    {
        if (!self::$_db) {
            self::$_db = $db;
        }
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
            /*
             * Instantiate variable if a value was given for it, otherwise, set
             * the variable to its default value via the schema.
             */
            if (isset($params[$variable])) {
                $this->$variable = $params[$variable];
            }
            else {
                if ($info['default']) {
                    $this->$variable = $info['default'];
                }
                else {
                    if ($info['default'] === null && $info['null'] === 'YES') {
                        // Only set other variables to NULL if a that is accepted. Otherwise,
                        // a value should be provided before save.
                        $this->$variable = null;
                    }
                }
            }
        }

        // Always instantiate the ID field even though it is requried
        if (!isset($this->{$this->_idField})) {
            $this->{$this->_idField} = null;
        }
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

    public static function findCount($params = array())
    {
        $params['count'] = true;
        $results = static::find($params);

        return $results[0]->{"COUNT(*)"};
    }

    public static function deleteById($id)
    {
        $idField = static::getIdField();
        $tableName = static::getTableName();
        $sth = self::$_db->prepare(
            "DELETE FROM {$tableName} WHERE {$idField} = :id;"
        );
        $success = $sth->execute(
            array(
                ':id' => $id,
            )
        );

        return $success;
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

    public static function findFirst($params = array())
    {
        $params['limit'] = 1;
        $results = static::find($params);

        return (!empty($results)) ? $results[0] : null;
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
        $returnObjects = true;

        $where = '';
        if (isset($params['conditions']) && $params['conditions']) {
            $where = 'WHERE ' . static::_buildFindConditions(
                    $params['conditions']
                );
        }

        $fields = '*';
        if (isset($params['fields']) && is_array($params['fields']) && $params['fields']) {
            $fields = implode(', ', $params['fields']);
        }
        else {
            if (isset($params['fields']) && is_string($params['fields']) && $params['fields']) {
                $fields = $params['fields'];
            }
        }

        if (isset($params['count']) && $params['count'] === true) {
            if ($fields === '*' || is_string($params['fields'])) {
                $fields = 'COUNT(' . $fields . ')';
                $returnObjects = false;
            }
        }

        $order = '';
        if (isset($params['order']) && $params['order']) {
            $order = 'ORDER BY ' . implode(', ', $params['order']);
        }

        $limit = '';
        if (isset($params['limit']) && $params['limit']) {
            $limit = 'LIMIT ' . $params['limit'];
        }

        $offset = '';
        if (isset($params['offset']) && $params['offset']) {
            $offset = 'OFFSET ' . $params['offset'];
        }

        $query = "SELECT $fields FROM {$tableName} $where $order $limit $offset;";

        $sth = self::$_db->prepare($query);
        $sth->execute(self::$_bindings);

        $results = array();
        foreach ($sth->fetchAll() as $result) {
            if ($returnObjects) {
                if ($result->created) {
                    $result->created = Carbon::createFromTimestampUTC(strtotime($result->created))->setTimezone(date_default_timezone_get());
                }
                if ($result->modified) {
                    $result->modified = Carbon::createFromTimestampUTC(strtotime($result->modified))->setTimezone(date_default_timezone_get());
                }
                $result->created = Carbon::createFromTimestampUTC(strtotime($result->created))->setTimezone(date_default_timezone_get());
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
        // @TODO: $this->User->find( 'all', array(
        //        'conditions' => array("not" => array ( "User.site_url" => null)
        //    ))
        //
        // Add 'not' functionality
        $retval = array();
        foreach ($conditions as $k => $v) {
            if ((strtoupper($k) === 'OR' || strtoupper($k) === 'AND') && is_array($v)) {
                $retval[] = '(' . static::_buildFindConditions(
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
                    self::$_bindings[":$k" . sizeof(self::$_bindings)] = $v;
                    return "$k IS NOT :$v";
                }
            }
            else {
                if (is_array($v)) {
                    $retval[] = static::_buildFindConditions($v);
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
                        $retval[] = "$k :$binding" . sizeof(self::$_bindings) . "";
                    }
                    else {
                        $retval[] = "$k = :$binding" . sizeof(self::$_bindings) . "";
                    }
                    self::$_bindings[":$binding" . sizeof(self::$_bindings)] = $v;
                }
            }
        }

        return implode(" $conjunction ", $retval);
    }

    public function getRelatedModels($relatedModel)
    {
        $retval = array();
        $relatedModel = $this->getModelName($relatedModel);

        if (array_key_exists($this->getForeignIdField($this->_className), $relatedModel::getSchema())) {
            $retval = call_user_func(
                array(
                    $relatedModel,
                    'find'
                ),
                array(
                    'conditions' => array(
                        $this->getForeignIdField($this->_className) => $this->id
                    )
                )
            );
        }

        return $retval;
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
        $this->validate();
        if (!empty($this->errors)) {
            return false;
        }

        if ($this->beforeSave() == false) {
            return false;
        }

        if (!$this->verifyRelationships()) {
            $this->errors[] = "Unable to verify relationships";
            return false;
        }

        self::$_bindings = array();
        $columns = array();
        $set = array();

        foreach ($this as $col => $val) {
            if ($col == 'created' || $col == 'modified') {
                if ($this->$col !== null) {
                    $this->$col = $this->$col->setTimezone('UTC')->toDateTimeString();
                }
            }
            else {
                if (array_key_exists($col, static::getSchema())) {
                    self::$_bindings[":$col"] = $val;

                    // @TODO: try and get rid of $set and $columns by using array walk. i.e. - this might not be any more efficient.
                    // Columns are used for INSERT
                    $columns[] = $col;
                    // Set array is used for UPDATE
                    $set[] = "$col = :$col";
                }
            }
        }

        if (!self::$_db->inTransaction()) {
            self::$_db->beginTransaction();
        }

        // If ID is not null, then UPDATE row in the database, else INSERT new row
        if ($this->{"{$this->_idField}"} !== null) {
            // Update query
            if (array_key_exists('modified', static::getSchema())) {
                $set[] = "modified = :modified";
                self::$_bindings[':modified'] = date("Y-m-d H:i:s");
            }

            $query = "UPDATE {$this->_tableName} SET " . implode(
                    ', ',
                    $set
                ) . " WHERE {$this->_idField} = :id_val";
            self::$_bindings[':id_val'] = $this->{"{$this->_idField}"};

            $sth = self::$_db->prepare($query);
            $success = $sth->execute(self::$_bindings);
        }
        else {
            // Insert query
            if (array_key_exists('created', static::getSchema())) {
                $columns[] = 'created';
                self::$_bindings[':created'] = date("Y-m-d H:i:s");
            }

            $query = "INSERT INTO {$this->_tableName} (" . implode(
                    ', ',
                    $columns
                ) . ") VALUES (" . implode(
                    ', ',
                    array_keys(self::$_bindings)
                ) . ")";

            $sth = self::$_db->prepare($query);

            $success = $sth->execute(self::$_bindings);
            $this->{"{$this->_idField}"} = self::$_db->lastInsertId();
        }

        /*
         * Here is where we handle various relationships
         */
//        if (isset($this->hasOne) && $this->hasOne) {
//            $hasOne = call_user_func(array($this->hasOne, 'findById'), $this->{$this->getForeignIdField($this->hasOne)});
//            if ($hasOne) {
//                $hasOne->{$this->getForeignIdField($this->_className)} = $this->{$this->getForeignIdField($this->hasOne)};
//                if (!$hasOne->save()) {
//                    self::$_db->rollBack();
//                    return false;
//                }
//            }
//        }

        if ($this->afterSave() == false) {
            self::$_db->rollBack();
            return false;
        }

        self::$_db->commit();

        return $success;
    }

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
                }
                else {
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
                $message = 'There was a problem rule name ' . strtoupper(
                        $rule
                    ) . ' for field ' . strtoupper($field);
                if (isset($info['message'])) {
                    $message = $info['message'];
                }

                if (!$this->$field) {
                    continue;
                }

                switch ($rule) {
                    case 'unique':
                        $results = $this->find(
                            array(
                                'conditions' => array(
                                    $field => $this->$field
                                )
                            )
                        );
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

    // @TODO: move validating to its own class

    private function verifyRelationships()
    {
        /*
         * Verify 'belongs to' relationships
         */
        if (isset($this->belongsTo)) {
            if (is_array($this->belongsTo)) {

            }
            else {
                if (is_string($this->belongsTo)) {
                    $ownerModel = $this->getModelName($this->belongsTo);
                    try {
                        $ownerId = $this->{$this->getForeignIdField(
                                $this->belongsTo
                            )};
                        $owner = call_user_func(
                            array(
                                $ownerModel,
                                'findById'
                            ),
                            $ownerId
                        );
                        if (!($owner instanceof $ownerModel)) {
                            return false;
                        }
                    } catch (Exception $e) {
                        return false;
                    }
                }
            }
        }

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

    public function delete()
    {
        $sth = self::$_db->prepare(
            "DELETE FROM {$this->_tableName} WHERE {$this->_idField} = :id;"
        );
        $success = $sth->execute(
            array(
                ':id' => $this->{$this->_idField},
            )
        );

        unset($this);

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
        return json_encode($this->getDBObject());
    }

    /**
     * Retrieve a representation of the object as it would exist in the database.
     * The returned object is a standard class of only the fields that are in
     * the database schema.
     *
     * @return stdClass
     */
    public function getDBObject()
    {
        $retval = new stdClass();
        foreach ($this as $key => $value) {
            if (array_key_exists($key, $this->getSchema())) {
                $retval->$key = $value;
            }
        }

        return $retval;
    }
}