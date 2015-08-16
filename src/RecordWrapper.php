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

use ntentan\utils\Utils;

class RecordWrapper implements \ArrayAccess, \Countable, \Iterator
{
    protected $hasMany = [];
    protected $belongsTo = [];

    protected $table;
    private $data = [];
    private $invalidFields;
    private $dynamicOperations;
    private $validator;
    private $index = 0;

    public function __construct()
    {
        Utils::factory(
            $this->table,
            function() {
                $class = new \ReflectionClass($this);
                $nameParts = explode("\\", $class->getName());
                return \ntentan\utils\Text::deCamelize(end($nameParts));
            }
        );
    }

    /**
     *
     * @return \ntentan\nibii\DriverAdapter
     */
    protected function getDataAdapter()
    {
        return DriverAdapter::getDefaultInstance();
    }

    protected function getDriver()
    {
        return $this->getDataAdapter()->getDriver();
    }

    public function getDescription()
    {
        return new ModelDescription($this);
    }
    
    public function count()
    {
        return $this->__call('count', []);
    }

    private function retrieveItem($key)
    {
        $relationships = $this->getDescription()->getRelationships();
        if(isset($relationships[$key])) {
            return $this->fetchRelatedFields($relationships[$key]);
        } else {
            return $this->data[$key];
        }
    }

    public static function createNew()
    {
        $class = get_called_class();
        return new $class();
    }

    public function validate()
    {
        $valid = true;
        $validator = Utils::factory($this->validator,
            function() {
                return new Validator($this->getDescription());
            }
        );
        $data = isset(func_get_args()[0]) ? [func_get_args()[0]] : $this->getData();

        foreach($data as $datum) {
            if(!$validator->validate($datum)) {
                $valid = false;
            }
        }

        if($valid === false) {
            $valid = $validator->getInvalidFields();
        }

        return $valid;
    }

    private function assignValue(&$property, $value)
    {
        if($this->hasMultipleData()) {
            $property = $value;
        } else {
            $property = $value[0];
        }
    }

    private function isPrimaryKeySet($primaryKey, $data)
    {
        foreach($primaryKey as $keyField) {
            if(!isset($data[$keyField])) {
                break;
            }
            if($data[$keyField] !== '' && $data[$keyField] !== null) {
                return true;
            }
        }
        return false;
    }

    private function saveRecord($datum, $primaryKey)
    {
        $status = [
            'success' => true,
            'pk_assigned' => null,
            'invalid_fields' => []
        ];

        $validity = $this->validate($datum);

        if($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }

        if($this->isPrimaryKeySet($primaryKey, $datum)) {
            $this->getDataAdapter()->update($datum);
        } else {
            $this->getDataAdapter()->insert($datum);
            $status['pk_assigned'] = $this->getDriver()->getLastInsertId();
        }

        return $status;
    }

    public function save()
    {
        $invalidFields = [];
        $data = $this->getData();
        $this->getDataAdapter()->setModel($this);
        $primaryKey = $this->getDescription()->getPrimaryKey();
        $singlePrimaryKey = null;
        $succesful = true;

        if (count($primaryKey) == 1) {
            $singlePrimaryKey = $primaryKey[0];
        }

        $this->getDriver()->beginTransaction();

        foreach($data as $i => $datum) {
            $status = $this->saveRecord($datum, $primaryKey);

            if(!$status['success']) {
                $succesful = false;
                $invalidFields[$i] = $status['invalid_fields'];
                $this->getDriver()->rollback();
                continue;
            }

            if($singlePrimaryKey) {
                $data[$i][$singlePrimaryKey] = $status['pk_assigned'];
            }
        }

        if($succesful) {
            $this->assignValue($this->data, $data);
            $this->getDriver()->commit();
        } else {
            $this->assignValue($this->invalidFields, $invalidFields);
        }

        return $succesful;
    }

    private static function getInstance()
    {
        $class = get_called_class();
        return new $class();
    }

    public function __call($name, $arguments)
    {
        return Utils::factory($this->dynamicOperations,
            function() {
                return new DynamicOperations($this, $this->getDataAdapter());
            }
        )->perform($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->retrieveItem($name);
    }

    public function getTable()
    {
        return $this->table;
    }
    
    private function expandArrayValue($array, $relationships, $index = null)
    {
        foreach($relationships as $name => $relationship) {
            $array[$name] = $this->fetchRelatedFields($relationship, $index)->toArray();
        }
        return $array;
    }

    public function toArray()
    {
        $relationships = $this->getDescription()->getRelationships();
        $array = $this->data;
        if($this->hasMultipleData()) {
            foreach($array as $i => $value) {
                $array[$i] = $this->expandArrayValue($value, $relationships, $i);
            }
        } else {
            $array = $this->expandArrayValue($array, $relationships);
        }
        return $array;
    }

    private function hasMultipleData()
    {
        if(count($this->data) > 0) {
            return is_numeric(array_keys($this->data)[0]);
        } else {
            return false;
        }
    }

    public function getData()
    {
        $data = [];

        if($this->hasMultipleData())
        {
            $data = $this->data;
        } else {
            $data[] = $this->data;
        }

        return $data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
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
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    private function wrap($offset)
    {
        if(isset($this->data[$offset])) {
            $newInstance = $this->createNew();
            $newInstance->setData($this->data[$offset]);
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
        return $this->wrap($this->index);
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->data[$this->index]);
    }

    private function fetchRelatedFields($relationship, $index = null)
    {
        if($index === null) {
            $data = $this->data;
        } else {
            $data = $this->data[$index];
        }
        $model = $relationship->getModelInstance();
        return $model->fetch($relationship->getQuery($data));
    }

    public function getRelationships()
    {
        return [
            'HasMany' => $this->hasMany,
            'BelongsTo' => $this->belongsTo
        ];
    }
}
