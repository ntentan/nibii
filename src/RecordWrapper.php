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
 * Wraps a record from the database with data manipulation operations.
 * Wrapping a table with the record wrapper makes it possible to add, edit,
 * delete and query the underlying database. An MVC framework can use the 
 * record wrapper as a base for its Model class.
 */
class RecordWrapper implements \ArrayAccess, \Countable, \Iterator
{

    protected $hasMany = [];
    protected $belongsTo = [];
    protected $manyHaveMany = [];
    protected $behaviours = [];
    protected $table;
    protected $schema;
    protected $modelData = [];
    private $quotedTable;
    private $unquotedTable;
    private $invalidFields;
    private $dynamicOperations;
    private $index = 0;
    private $dataSet = false;
    private $className;

    /**
     *
     * @var DriverAdapter
     */
    private $adapter;
    private $container;
    private $context;
    private $keys = [];
    private $initialized = false;

    /**
     * Initialize the record wrapper and setup the adapters, drivers, tables and schemas.
     * 
     * @return void
     */
    protected function initialize() : void
    {
        if ($this->initialized) {
            return;
        }
        $this->context = ORMContext::getInstance();
        $this->adapter = $this->container->resolve(DriverAdapter::class);
        $table = $this->table ?? $this->context->getModelTable($this);
        $driver = $this->context->getDbContext()->getDriver();
        $this->adapter->setContext($this->context);
        $this->className = (new \ReflectionClass($this))->getName();
        if (is_string($table)) {
            //$this->quotedTable = $driver->quoteIdentifier($table);
            $this->table = $this->unquotedTable = $table;
        } else {
            $this->table = $table['table'];
            $this->schema = $table['schema'];
        }
        $this->quotedTable = ($this->schema ? "{$driver->quoteIdentifier($this->schema)}." : "") . $driver->quoteIdentifier($this->table);
        $this->unquotedTable = ($this->schema ? "{$this->schema}." : "") . $this->table;
        $this->adapter->setModel($this, $this->quotedTable);
        foreach ($this->behaviours as $behaviour) {
            $behaviourInstance = $this->getComponentInstance($behaviour);
            $behaviourInstance->setModel($this);
        }
        $this->initialized = true;
    }

    public function __debugInfo()
    {
        $data = $this->getData();
        return $this->hasMultipleItems() ? $data : isset($data[0]) ? $data[0] : [];
    }

    /**
     * 
     * @return ModelDescription
     */
    public function getDescription()
    {
        $this->initialize();
        return $this->context->getCache()->read(
                        (new \ReflectionClass($this))->getName() . '::desc', function() {
                    return $this->container->resolve(ModelDescription::class, ['model' => $this]);
                }
        );
    }

    /**
     * Return the number of items stored in the model or the query.
     * @return integer
     */
    public function count()
    {
        if ($this->dataSet) {
            return count($this->getData());
        } else {
            return $this->__call('count', []);
        }
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
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public function __call($name, $arguments)
    {
        $this->initialize();
        if ($this->dynamicOperations === null) {
            // Bind to existing instances
            $this->container->bind(RecordWrapper::class)->to($this);
            $this->dynamicOperations = $this->container->resolve(
                    Operations::class, ['table' => $this->quotedTable, 'adapter' => $this->adapter]
            );
            // Unbind all bindings (necessary?)
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

    private function expandArrayValue($array, $relationships, $depth, $index = null)
    {
        foreach ($relationships as $name => $relationship) {
            $array[$name] = $this->fetchRelatedFields($relationship, $index)->toArray($depth);
        }
        return $array;
    }

    public function toArray($depth = 0)
    {
        $relationships = $this->getDescription()->getRelationships();
        $array = $this->modelData;
        if ($depth > 0) {
            if ($this->hasMultipleItems()) {
                foreach ($array as $i => $value) {
                    $array[$i] = $this->expandArrayValue($value, $relationships, $depth - 1, $i);
                }
            } else {
                $array = $this->expandArrayValue($array, $relationships, $depth - 1);
            }
        }
        return $array;
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
        $this->dataSet = true;
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
            $newInstance = $this->container->resolve($this->className);
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
     * @return DataAdapter
     */
    public function getAdapter()
    {
        $this->initialize();
        return $this->adapter;
    }

}
