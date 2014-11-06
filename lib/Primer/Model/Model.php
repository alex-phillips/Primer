<?php

namespace Primer\Model;

use stdClass;
use DateTime;
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
    /**
     * Data structure in which all unique variables related to the database-representation
     * of the object are stored. These are accessed like normal, public class
     * variables through magic __get and __set methods.
     *
     * @var array
     */
    public $data = array();

    /**
     * Array that contains validation and save error messages
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Variable that contains information regarding the models 'has one'
     * relationships.
     *
     * @var string|array
     */
    protected static $_hasOne = '';

    /**
     * Variable that contains information regarding the models 'has many'
     * relationships.
     *
     * @var string|array
     */
    protected static $_hasMany = '';

    /**
     * Variable that contains information regarding the models 'belongs to'
     * relationships.
     *
     * @var string|array
     */
    protected static $_belongsTo = '';

    /**
     * Variable that contains information regarding the models 'has and belongs to many'
     * relationships.
     *
     * @var string|array
     */
    protected static $_hasAndBelongsToMany = '';

    /**
     * This is the ID field in the database for the object. This is stored so
     * we know what the primary key for the table is for each model.
     *
     * @var string
     */
    protected $_idField;

    /**
     * Name of the model's table in the database. This is set automatically
     * unless overridden.
     *
     * @var string
     */
    protected $_tableName;

    /**
     * Name of the current instance's model. Used for automatically creating
     * new instances when returning objects. Ex: user, post
     *
     * @var string
     */
    protected $_className;

    /**
     * Schema variable is built from the table in the database for the Model.
     * This is used to determine what values to set, default values, and what
     * to insert and update in the database when the save() method is called.
     *
     * @var array
     */
    protected static $_schema = array();

    /**
     * Validation array contains rules to check on each model field.
     * This is not validation for forms or client-side validation, but
     * validation before a model is created or updated in the database.
     *
     * @var array
     */
    protected static $_validate = array();

    /**
     * Database variable to handle all query creations and executions.
     *
     * @var
     */
    protected static $_db;

    /**
     * Array of variables to pass to PDO to bind in preparing DB queries
     *
     * @var array
     */
    protected static $_bindings = array();

    /**
     * Constructor for every model class. This is protected as every model
     * instantiated outside of this class should use the static function
     * 'create'.
     *
     * @param array $params
     */
    protected function __construct($params = array())
    {
        $this->_idField = $this->getIdField();
        $this->_tableName = $this->getTableName();
        $this->_className = $this->getModelName();

        static::getSchema();
        if (!empty($params)) {
            $this->set($params);
        }
    }

    /**
     * Publicly accessible function used to create a new instance of the model
     * with the passed params used to set necessary variables.
     *
     * @param array $params
     *
     * @return static
     */
    public static function create($params = array())
    {
        return new static($params);
    }

    public function __get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    public function __set($key, $value)
    {
        /*
         * Make sure that created and modified properties are Carbon objects.
         */
        if (array_key_exists($key, static::getSchema())) {
            if ($key === 'created' || $key === 'modified') {
                if ($value === null) {
                    $this->data[$key] = null;
                }
                else if ($value instanceof Carbon) {
                    $this->data[$key] = $value;
                }
                else {
                    if (is_numeric($value)) {
                        $this->data[$key] = Carbon::createFromTimestamp($value);
                    }
                    else {
                        $this->data[$key] = Carbon::createFromTimestamp(strtotime($value));
                    }
                }
            }
            else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Returns the default database ID field for the Model
     *
     * @return string
     */
    public static function getIdField($class = null)
    {
        return 'id';
    }

    /**
     * Returns the default table name for a model
     * Ex: User model returns 'users'
     *
     * @return string
     */
    public static function getTableName($class = null)
    {
        $class = $class ?: get_called_class();

        return Inflector::tableize($class);
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

    /**
     * This function queries the database for the structure of a given class
     * and returns the structure. It is 'cached' and stored in a data structure
     * so that each model's schema is queried from the database only once per
     * active request.
     *
     * @param null $class
     *
     * @return mixed
     */
    public static function getSchema($class = null)
    {
        $modelName = static::getModelName($class);
        $tableName = static::getTableName($class);
        if (!isset(static::$_schema[$modelName])) {
            $query = "DESCRIBE {$tableName};";
            $sth = self::$_db->prepare($query);
            $sth->execute(self::$_bindings);
            $fields = $sth->fetchAll();

            foreach ($fields as $info) {
                static::$_schema[$modelName][$info->Field] = array(
                    'type' => $info->Type,
                    'null' => $info->Null,
                    'key' => $info->Key,
                    'default' => $info->Default,
                    'extra' => $info->Extra
                );
            }
        }

        return static::$_schema[$modelName];
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
        if (!$this->{$this->_idField}) {
            $this->{$this->_idField} = null;
        }
    }

    public function getErrors()
    {
        return $this->_errors;
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
     * Given an array of params to build a query with, this function returns
     * the count found in the database.
     *
     * @param array $params
     *
     * @return mixed
     */
    public static function findCount($params = array())
    {
        $params['count'] = true;
        $results = static::find($params);

        return $results[0]->{"COUNT(*)"};
    }

    /**
     * Delete a model with the given ID.
     *
     * @param $id
     *
     * @return mixed
     */
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

    /**
     * Call the 'find' method with an automatic limit of 1.
     *
     * @param array $params
     *
     * @return mixed|null
     */
    public static function findFirst($params = array())
    {
        $params['limit'] = 1;
        $results = static::find($params);

        return (!empty($results)) ? array_shift($results) : null;
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
        $modelName = static::getModelName();
        self::$_bindings = array();
        $returnObjects = true;

        $params = array_merge(
            array(
                'conditions' => '',
                'fields'     => '',
                'joins'      => array(),
                'limit'      => '',
                'offset'     => '',
                'order'      => '',
                'count'      => false,
            ),
            $params
        );

        if ($params['count']) {
            $returnObjects = false;
        }

        $query = static::buildQuery($params);

        $sth = self::$_db->prepare($query);
        $sth->execute(self::$_bindings);

        $results = array();
        foreach ($sth->fetchAll() as $result) {
            $id = null;
            if ($returnObjects) {
                $foreignObjects = static::buildForeignObjects($result);

                $id = $result->{static::getIdField()};

                if (isset($results[$id])) {
                    $result = $results[$id];
                }
                else {
                    $result = new $modelName($result);
                }

                foreach ($foreignObjects as $foreignModelName => $object) {
                    $relationship = static::verifyRelationship($foreignModelName);

                    switch ($relationship) {
                        case 'hasOne':
                        case 'belongsTo':
                            $result->{$foreignModelName} = new $foreignModelName($object);
                            break;
                        default:
                            $foreignId = $object->{static::getIdField($foreignModelName)};
                            if (!isset($result->{$foreignModelName}[$foreignId])) {
                                $result->{$foreignModelName}[$foreignId] = new $foreignModelName($object);
                            }
                    }
                }

                $results[$id] = $result;
            }
            else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Given a database row, this function takes each variable that does not exist
     * in the current model and builds and returns an array of other objects that
     * were retrieved in the row.
     *
     * @param $row
     *
     * @return array
     */
    protected static function buildForeignObjects($row)
    {
        $foreignObjects = array();
        foreach ($row as $k => $v) {
            if (!array_key_exists($k, static::getSchema())) {
                if (preg_match('#\A(.+?)_#', $k, $matches)) {
                    $foreignModelName = $matches[1];
                    unset($row->$k);
                    if (!isset($foreignObjects[$foreignModelName])) {
                        $foreignObjects[$foreignModelName] = new \stdClass();
                    }
                    $k = preg_replace('#' . $matches[1] . '_#', '', $k);
                    $foreignObjects[$foreignModelName]->$k = $v;
                }
            }
        }

        return $foreignObjects;
    }

    /**
     * Given a params array, this function builds and returns a SQL query used
     * to retrieve data from a MySQL database.
     *
     * @param $params
     *
     * @return string
     */
    protected static function buildQuery($params)
    {
        $tableName = static::getTableName();
        $modelName = static::getModelName();

        $params['conditions'] = $params['conditions'] ? 'WHERE ' . static::buildFindConditions($params['conditions']) : '';
        $params['limit'] = $params['limit'] ? "LIMIT {$params['limit']}" : '';
        $params['offset'] = $params['offset'] ? "OFFSET {$params['offset']}" : '';

        if ($params['fields']) {
            if (is_array($params['fields'])) {
                $params['fields'] = implode(', ', $params['fields']);
            }
        }
        else {
            $params['fields'] = static::getModelName() . ".*";
        }

        if ($params['order']) {
            if (is_array($params['order'])) {
                $params['order'] = 'ORDER BY ' . implode(', ', $params['order']);
            }
            else {
                $params['order'] = "ORDER BY {$params['order']}";
            }
        }

        if (!$params['joins']) {
            $params['joins'] = static::buildJoins();
        }

        if ($params['count'] === true) {
            $params['joins'] = array();
            if (!preg_match('#\ADISTINCT#i', $params['fields'])) {
                $params['fields'] = "COUNT(*)";
            }
            else {
                $params['fields'] = "COUNT({$params['fields']})";
            }
        }

        $joins = array();
        foreach ($params['joins'] as $join) {
            $default = array(
                'alias'      => static::getModelName($join['table']),
                'type'       => 'LEFT',
                'conditions' => array(),
            );
            $join = array_merge($default, $join);

            if (!$join['conditions']) {
                $join['conditions'] = "{$join['alias']}." . static::getForeignIdField() . " = " . static::getModelName() . "." . static::getIdField();
            }
            else if (is_array($join['conditions'])) {
                $join['conditions'] = implode(' AND ', $join['conditions']);
            }

            if ($join['alias']) {
                $join['alias'] = "{$join['alias']}";
            }

            $joins[] = "{$join['type']} JOIN {$join['table']} AS {$join['alias']} ON {$join['conditions']}";
            foreach (static::getSchema($join['table']) as $k => $v) {
                $params['fields'] .= ", {$join['alias']}.$k as {$join['alias']}_$k";
            }
        }
        $joins = implode(', ', $joins);

        return "SELECT {$params['fields']} FROM {$tableName} AS {$modelName} {$joins} {$params['conditions']} {$params['order']} {$params['limit']} {$params['offset']};";
    }

    protected static function buildJoins()
    {
        $joins = array();

        // Build 'hasOne' case
        if (static::$_hasOne) {
            if (!is_array(static::$_hasOne)) {
                $joins[] = array(
                    'table' => static::getTableName(static::$_hasOne),
                );
            }
            else {
                // handle array
            }
        }

        if (static::$_belongsTo) {
            if (!is_array(static::$_belongsTo)) {
                $joins[] = array(
                    'table' => static::getTableName(static::$_belongsTo),
                    'alias' => static::getModelName(static::$_belongsTo),
                    'conditions' => array(
                        static::getModelName() . "." . static::getForeignIdField(static::$_belongsTo) . " = " . static::getModelName(static::$_belongsTo) . "." . static::getIdField(static::$_belongsTo),
                    ),
                );
            }
            else {
                // handle array
            }
        }

        return $joins;
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
    protected static function buildFindConditions($conditions, $conjunction = '')
    {
        // @TODO: $this->User->find( 'all', array(
        //        'conditions' => array("not" => array ( "User.site_url" => null)
        //    ))
        //
        // Add 'not' functionality
        $retval = array();
        foreach ($conditions as $k => $v) {
            if ((strtoupper($k) === 'OR' || strtoupper($k) === 'AND') && is_array($v)) {
                $retval[] = '(' . static::buildFindConditions(
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

                    return static::getModelName() . ".$k IS NOT :$v";
                }
            }
            else {
                if (is_array($v)) {
                    $retval[] = static::buildFindConditions($v);
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
                        $retval[] = static::getModelName() . ".$k :$binding" . sizeof(self::$_bindings) . "";
                    }
                    else {
                        $retval[] = static::getModelName() . ".$k = :$binding" . sizeof(self::$_bindings) . "";
                    }
                    self::$_bindings[":$binding" . sizeof(self::$_bindings)] = $v;
                }
            }
        }

        return implode(" $conjunction ", $retval);
    }

    /**
     * Retrieve and returns the models that are related to the current model
     * object based on current ID and foreign ID fields.
     *
     * @param $relatedModel
     *
     * @return array|mixed
     */
    protected function getRelatedModels($relatedModel)
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
    public static function getForeignIdField($class = null)
    {
        $class = $class ?: get_called_class();

        return 'id_' . strtolower(self::getModelName($class));
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
        if (!empty($this->_errors)) {
            return false;
        }

        if ($this->beforeSave() == false) {
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
        if ($this->{"{$this->_idField}"}) {
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

        if ($this->afterSave() == false) {
            self::$_db->rollBack();
            return false;
        }

        self::$_db->commit();

        return $success;
    }

    private function validate($ruleSet = null)
    {
        if (!$ruleSet) {
            $ruleSet = static::$_validate;
        }

        foreach ($ruleSet as $field => $rules) {
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
                    $this->_errors[] = $message;
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
                                    $this->_errors[] = $message;
                                }
                            }
                        }
                        break;
                    // Validate e-mail
                    case 'email':
                        if (!filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate alpha-numeric field
                    case 'alphaNumeric':
                        if (!ctype_alnum($this->$field)) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate numeric field
                    case 'numeric':
                        if (!is_numeric($this->$field)) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate max length
                    case 'max_length':
                        if (strlen($this->$field) > $info['size']) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate min length
                    case 'min_length':
                        if (strlen($this->$field) < $info['size']) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate list of options
                    case 'in_list':
                        if (!in_array($this->$field, $info['list'])) {
                            $this->_errors[] = $message;
                        }
                        break;
                    // Validate custom regex
                    case 'regex':
                        if (!preg_match($info['rule'], $this->$field)) {
                            $this->_errors[] = $message;
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

    /**
     * Determines and returns the relationship of the passed model name
     * to the current model object.
     *
     * @param $foreignModelName
     *
     * @return string
     */
    protected static function verifyRelationship($foreignModelName)
    {
        $relationship = '';

        // Check if model 'hasOne' foreign model
        if (!$relationship && static::$_hasOne) {
            if (is_array(static::$_hasOne)) {
                foreach (static::$_hasOne as $identifier => $info) {
                    if ($info['className'] == $foreignModelName) {
                        $relationship = 'hasOne';
                        break;
                    }
                }
            }
            else {
                if ($foreignModelName == static::$_hasOne) {
                    $relationship = 'hasOne';
                }
            }
        }

        if (!$relationship && static::$_belongsTo) {
            if (is_array(static::$_belongsTo)) {
                foreach (static::$_belongsTo as $identifier => $info) {
                    if ($info['className'] == $foreignModelName) {
                        $relationship = 'belongsTo';
                        break;
                    }
                }
            }
            else {
                if ($foreignModelName == static::$_belongsTo) {
                    $relationship = 'belongsTo';
                }
            }
        }

        return $relationship;
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
     * Delete the object from the database
     *
     * @return bool
     */
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
        $retval = new \stdClass();
        foreach ($this as $key => $value) {
            if (array_key_exists($key, $this->getSchema())) {
                $retval->$key = $value;
            }
        }

        return $retval;
    }

    /**
     * Function used to convert any variable into a form that can be JSON
     * serialized.
     *
     * @param $o
     *
     * @return array|stdClass
     */
    public static function toStdClass($o)
    {
        if ($o instanceof DateTime) {
            return $o->getTimestamp();
        }

        if (is_array($o)) {
            $o_new = array();
            foreach ($o as $oitem) {
                $o_new[] = self::toStdClass($oitem);
            }

            return $o_new;
        }

        if (!is_object($o)) {
            if (is_numeric($o)) {
                $vint = intval($o);
                $vfloat = floatval($o);
                $v = ($vfloat != $vint) ? $vfloat : $vint;

                return $v;
            }

            return $o;
        }

        $xary = (array)$o;
        $o_new = new stdClass ();
        foreach ($xary as $k => $v) {
            if ($k[0] == "\0") {
                // private/protected members have null-delimited prefixes
                // that need to be removed
                $prefix_length = stripos($k, "\0", 1) + 1;
                $k = substr($k, $prefix_length, strlen($k) - $prefix_length);
            }

            $o_new->$k = self::toStdClass($v);
        }

        return $o_new;
    }
}