<?php

/*
 * The MIT License
 *
 * Copyright 2015 ekow.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ntentan\nibii;

use ntentan\utils\Text;

/**
 * An active record wrapper for database records.
 */
class RecordWrapper implements \ArrayAccess, \Countable, \Iterator
{

    /**
     * An associative array of models to which this model has a one to may relationship.
     *
     * @var array
     */
    protected $hasMany = [];

    /**
     * An associative array of models which have a one-to-many relationship with this model.
     *
     * @var array
     */
    protected $belongsTo = [];

    /**
     * An associative array of models with which this model has a many to many relationship.
     *
     * @var array
     */
    protected $manyHaveMany = [];

    /**
     * The name of the database table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the schema to which this table belongs.
     *
     * @var string
     */
    protected $schema;

    /**
     * Temporary data held in the model object.
     *
     * @var array
     */
    protected $modelData = [];

    /**
     * A quoted string of the table name used for building queries.
     *
     * @var string
     */
    private $quotedTable;
    private $unquotedTable;
    private $invalidFields;
    private $dynamicOperations;
    private $index = 0;
    private $dataSet = false;
    private $className;

    /**
     * An instance of the driver adapter for interacting with the database.
     * @var DriverAdapter
     */
    private $adapter;
    private $context;
    private $keys = [];
    private $initialized = false;

    /**
     * Initialize the record wrapper and setup the adapters, drivers, tables and schemas.
     * @return void
     */
    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->context = ORMContext::getInstance();
        $this->adapter = $this->context->getDriverAdapter();
        $table = $this->table ?? $this->context->getModelTable($this);
        $driver = $this->context->getDbContext()->getDriver();
        $this->adapter->setContext($this->context);
        $this->className = (new \ReflectionClass($this))->getName();
        if (is_string($table)) {
            $this->table = $this->unquotedTable = $table;
        } else {
            $this->table = $table['table'];
            $this->schema = $table['schema'];
        }
        $this->quotedTable = ($this->schema ? "{$driver->quoteIdentifier($this->schema)}." : "") . $driver->quoteIdentifier($this->table);
        $this->unquotedTable = ($this->schema ? "{$this->schema}." : "") . $this->table;
        $this->adapter->setModel($this, $this->quotedTable);
        $this->initialized = true;
    }

    public function __debugInfo()
    {
        $data = $this->getData();
        return $this->hasMultipleItems() ? $data : isset($data[0]) ? $data[0] : [];
    }

    /**
     * Return a description of the model wrapped by this wrapper.
     * @return ModelDescription
     */
    public function getDescription()
    {
        $this->initialize();
        return $this->context->getCache()->read(
                "{$this->className}::desc", function () {
                return $this->context->getModelDescription($this);
            }
        );
    }

    /**
     * Return the number of items stored in the model or matched by the query.
     * Depending on the state of the model, the count method will return different values. For models that have data
     * values set with calls to setData, this method returns the number of records that were added. On the other hand,
     * for models that do not have data set, this method queries the database to find out the number of records that
     * are either in the model, or for models that have been filtered, the number of records that match the filter.
     *
     * @param int|array|QueryParameters $query
     * @return int
     */
    public function count($query = null)
    {
        if ($this->dataSet) {
            return count($this->getData());
        }
        return $this->__call('count', [$query]);
    }

    /**
     * Retrieve an item stored in the record.
     * This method returns items that are directly stored in the model or lazy
     * loads related items. The key could be a field in the model's table or
     * the name of a related model.
     * @param string $key A key identifying the item to be retrieved.
     * @return mixed
     */
    private function retrieveItem($key)
    {
        $relationships = $this->getDescription()->getRelationships();
        $decamelizedKey = Text::deCamelize($key);
        if (isset($relationships[$key])) {
            return $this->fetchRelatedFields($relationships[$key]);
        }
        return isset($this->modelData[$decamelizedKey]) ? $this->modelData[$decamelizedKey] : null;
    }

    /**
     * @method
     * @param string $name
     * @param array $arguments
     * @return type
     */
    public function __call($name, $arguments)
    {
        $this->initialize();
        if ($this->dynamicOperations === null) {
            $this->dynamicOperations = new Operations($this, $this->quotedTable);
        }
        return $this->dynamicOperations->perform($name, $arguments);
    }

    public function __set($name, $value)
    {
        $this->dataSet = true;
        $this->modelData[Text::deCamelize($name)] = $value;
    }

    public function __get($name)
    {
        return $this->retrieveItem($name);
    }

    public function save()
    {
        $return = $this->__call('save', [$this->hasMultipleItems()]);
        $this->invalidFields = $this->dynamicOperations->getInvalidFields();
        return $return;
    }

    private function hasMultipleItems()
    {
        if (count($this->modelData) > 0) {
            return is_numeric(array_keys($this->modelData)[0]);
        } else {
            return false;
        }
    }

    public function getData()
    {
        $data = [];

        if (count($this->modelData) == 0) {
            $data = $this->modelData;
        } else if ($this->hasMultipleItems()) {
            $data = $this->modelData;
        } else if (count($this->modelData) > 0) {
            $data[] = $this->modelData;
        }

        return $data;
    }

    public function setData($data)
    {
        $this->dataSet = $data ? true : false;
        $this->modelData = $data;
    }

    public function mergeData($data)
    {
        foreach ($data as $key => $value) {
            $this->modelData[$key] = $value;
        }
        $this->dataSet = true;
    }

    public function offsetExists($offset)
    {
        return isset($this->modelData[$offset]);
    }

    public function offsetGet($offset)
    {
        if (is_numeric($offset)) {
            return $this->wrap($offset);
        } else {
            return $this->retrieveItem($offset);
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->dataSet = true;
        $this->modelData[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->modelData[$offset]);
    }

    private function wrap($offset)
    {
        $this->initialize();
        if (isset($this->modelData[$offset])) {
            $className = $this->className;
            $newInstance = new $className();
            $newInstance->initialize();
            $newInstance->setData($this->modelData[$offset]);
            return $newInstance;
        } else {
            return null;
        }
    }

    public function getInvalidFields()
    {
        return $this->invalidFields;
    }

    public function getHasMany()
    {
        return $this->hasMany;
    }

    public function getBelongsTo()
    {
        return $this->belongsTo;
    }

    public function current()
    {
        return $this->wrap($this->keys[$this->index]);
    }

    public function key()
    {
        return $this->keys[$this->index];
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->keys = array_keys($this->modelData);
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->keys[$this->index]) && isset($this->modelData[$this->keys[$this->index]]);
    }

    public function onValidate($errors)
    {
        return $errors;
    }

    private function fetchRelatedFields($relationship, $index = null)
    {
        if ($index === null) {
            $data = $this->modelData;
        } else {
            $data = $this->modelData[$index];
        }
        $model = $relationship->getModelInstance();
        if (empty($data)) {
            return $model;
        }
        $query = $relationship->prepareQuery($data);
        return $query ? $model->fetch($query) : $model;
    }

    public function getRelationships()
    {
        return [
            'HasMany' => $this->hasMany,
            'BelongsTo' => $this->belongsTo,
            'ManyHaveMany' => $this->manyHaveMany
        ];
    }

    public function usetField($field)
    {
        unset($this->modelData[$field]);
    }

    public function preSaveCallback()
    {
        
    }

    public function postSaveCallback($id)
    {
        
    }

    public function preUpdateCallback()
    {
        
    }

    public function postUpdateCallback()
    {
        
    }

    public function getDBStoreInformation()
    {
        $this->initialize();
        return [
            'schema' => $this->schema,
            'table' => $this->table,
            'quoted_table' => $this->quotedTable,
            'unquoted_table' => $this->unquotedTable
        ];
    }

    /**
     *
     * @return DriverAdapter
     */
    public function getAdapter()
    {
        $this->initialize();
        return $this->adapter;
    }

    private function expandArrayValue($array, $relationships, $depth, $expandOnly = [], $index = null)
    {
        if(empty($expandOnly)){
            foreach ($relationships as $name => $relationship) {
                $array[$name] = $this->fetchRelatedFields($relationship, $index)->toArray($depth);
            }
        } else {
            foreach ($expandOnly as $name) {
                $array[$name] = $this->fetchRelatedFields($relationships[$name], $index)->toArray($depth, $expandOnly);
            }
        }
        return $array;
    }

    public function toArray($depth = 0, $expandOnly = [])
    {
        $relationships = $this->getDescription()->getRelationships();
        $array = $this->modelData;
        if ($depth > 0) {
            if ($this->hasMultipleItems()) {
                foreach ($array as $i => $value) {
                    $array[$i] = $this->expandArrayValue($value, $relationships, $depth - 1, $expandOnly, $i);
                }
            } else {
                $array = $this->expandArrayValue($array, $relationships, $depth - 1, $expandOnly);
            }
        }
        return $array;
    }

}
