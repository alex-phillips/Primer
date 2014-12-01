<?php

namespace Primer\Data;

use IteratorAggregate;
use Serializable;
use JsonSerializable;
use Countable;
use stdClass;
use ArrayIterator;
use DateTime;
use Carbon\Carbon;
use Primer\Core\Object;
use Primer\Utility\Inflector;

/**
 * Class Model
 * @author Alex Phillips
 *
 * Class in which all models are inherited from. Contains all 'generic' database
 * interactions and validation for models.
 */
abstract class Model extends Object implements IteratorAggregate, Serializable, JsonSerializable, Countable
{
    /**
     * Data structure in which all unique variables related to the database-representation
     * of the object are stored. These are accessed like normal, public class
     * variables through magic __get and __set methods.
     *
     * @var array
     */
    protected $_data = array();

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
     * Schema variable is built from the table in the database for the Model.
     * This is used to determine what values to set, default values, and what
     * to insert and update in the database when the save() method is called.
     *
     * @var array
     */
    protected static $_schema = array();

    /**
     * Data structure to store a 'meta instance' of each model object for
     * reference of properties, schema, etc.
     *
     * @var array
     */
    protected static $_metaInstanceCache = array();

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
     * @var Database
     */
    protected static $_db;

    /**
     * @var Query
     */
    protected static $_query;

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
     */
    protected function __construct()
    {
        $this->_idField = $this->getIdField();
        $this->_tableName = $this->getTableName();

        if (!static::$_db) {
            static::$_db = Database::getInstance();
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
        $model = new static();
        static::getSchema();
        if (!empty($params)) {
            $model->set($params);
        }

        return $model;
    }

    public function __get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }

        return null;
    }

    public function __set($key, $value)
    {
        /*
         * Handle special cases for database properties
         */
        $schema = static::getSchema();
        if (array_key_exists($key, $schema)) {
            if ($schema[$key]['type'] == 'datetime') {
                if ($value === null) {
                    $this->_data[$key] = null;
                }
                else if ($value instanceof Carbon) {
                    $this->_data[$key] = $value;
                }
                else {
                    if (is_numeric($value)) {
                        $this->_data[$key] = Carbon::createFromTimestamp($value);
                    }
                    else {
                        $this->_data[$key] = Carbon::createFromTimestamp(strtotime($value));
                    }
                }
            }
            else {
                $this->_data[$key] = $value;
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
            $result = static::queryBuilder()->describe($tableName)->executeAndFetchAll();
            foreach ($result as $info) {
                switch (static::$_db->getType()) {
                    case 'mysql':
                        static::$_schema[$modelName][$info->Field] = array(
                            'type' => $info->Type,
                            'null' => $info->Null,
                            'key' => $info->Key,
                            'default' => $info->Default,
                        );
                        break;
                    case 'sqlite3':
                        static::$_schema[$modelName][$info->name] = array(
                            'type' => $info->type,
                            'null' => $info->notnull ? false : true,
                            'key' => $info->pk ? true : false,
                            'default' => $info->dflt_value,
                        );
                        break;
                }
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
        $result = static::queryBuilder()->count()->where($params)->executeAndFetch();
        if (!$result) {
            return null;
        }

        return $result[0];
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
        return static::queryBuilder()->delete()->where(array('id' => $id))->execute();
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

    protected static function queryBuilder()
    {
        return new ModelQueryBuilder(static::metaInstance());
    }

    protected static function metaInstance()
    {
        return static::getMetaInstance(get_called_class());
    }

    public static function getMetaInstance($class)
    {
        $class = static::getModelName($class);
        if (!isset(static::$_metaInstanceCache[$class])) {
            static::$_metaInstanceCache[$class] = new $class();
        }

        return static::$_metaInstanceCache[$class];
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

        $rows = static::queryBuilder()->select($params['fields'])->where($params['conditions'])->limit($params['limit'])->executeAndFetchAll();

        $results = array();
        foreach ($rows as $result) {
            $id = null;
            if ($returnObjects) {
                $foreignObjects = static::buildForeignObjects($result);

                $id = $result->{static::getIdField()};

                /*
                 * Format and handle any necessary columns to convert the database
                 * object to a model. i.e. Convert UTC dates to current timezone.
                 */
                foreach (static::getSchema($modelName) as $k => $v) {
                    if ($v['type'] == 'datetime' && isset($result->$k)) {
                        $result->$k = $result->$k ? Carbon::createFromFormat('Y-m-d H:i:s', $result->$k, 'GMT')->setTimezone(date_default_timezone_get()) : $result->$k;
                    }
                }

                if (isset($results[$id])) {
                    $result = $results[$id];
                }
                else {
                    $result = $modelName::create($result);
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

        if (array_key_exists($this->getForeignIdField(static::getModelName()), $relatedModel::getSchema())) {
            $retval = call_user_func(
                array(
                    $relatedModel,
                    'find'
                ),
                array(
                    'conditions' => array(
                        $this->getForeignIdField(static::getModelName()) => $this->id
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

        $schema = static::getSchema();
        $saveValues = array();
        foreach ($this as $col => $val) {
            if ($schema[$col]['type'] == 'datetime' && $this->$col !== null) {
                $val = $this->$col->setTimezone('UTC')->toDateTimeString();
            }

            if ($col == 'created' || $col == 'modified') {
                continue;
            }

            if (array_key_exists($col, $schema)) {
                $saveValues[$col] = $val;
            }
        }

        // If ID is not null, then UPDATE row in the database, else INSERT new row
        if ($this->{"{$this->_idField}"}) {
            // Update query
            if (array_key_exists('modified', static::getSchema())) {
                $this->modified = Carbon::now();
                $saveValues['modified'] = $this->modified->setTimezone('UTC')->toDateTimeString();
            }

            $success = static::queryBuilder()->update($saveValues)->where(array('id' => $this->{$this->_idField}))->execute();
        }
        else {
            // Insert query
            if (array_key_exists('created', static::getSchema())) {
                $this->created = Carbon::now();
                $saveValues['created'] = $this->created->setTimezone('UTC')->toDateTimeString();
            }

            $success = static::queryBuilder()->insert($saveValues)->execute();

            $this->{"{$this->_idField}"} = self::$_db->lastInsertId();
        }

        if ($this->afterSave() == false) {
            return false;
        }

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
        return static::queryBuilder()->delete()->where(array('id' => $this->{$this->_idField}))->execute();
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
        return $this->toStdClass($this->_data);
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

    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

    public function serialize()
    {
        return serialize($this->_data);
    }

    public function unserialize($data)
    {
        $this->_data = unserialize($data);
    }

    public function getData()
    {
        return $this->_data;
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }

    public function count()
    {
        return count($this->_data);
    }
}