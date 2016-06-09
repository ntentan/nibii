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
use ntentan\kaikai\Cache;
use ntentan\panie\InjectionContainer;
use ntentan\utils\Text;

class RecordWrapper implements \ArrayAccess, \Countable, \Iterator
{
    use \ntentan\panie\ComponentContainerTrait;
    
    protected $hasMany = [];
    protected $belongsTo = [];
    protected $manyHaveMany = [];
    protected $behaviours = [];
    protected $table;
    private $modelData = [];
    private $invalidFields;
    private $dynamicOperations;
    private $index = 0;
    private $dataSet = false;
    protected $adapter;

    public function __construct(DriverAdapter $adapter)
    {
        $this->table = Nibii::getModelTable($this);
        $this->adapter = $adapter;
        foreach($this->behaviours as $behaviour) {
            $behaviourInstance = $this->getComponentInstance($behaviour);
            $behaviourInstance->setModel($this);
        }
    }

    /**
     * 
     * @return ModelDescription
     */
    public function getDescription()
    {
        return Cache::read(
            (new \ReflectionClass($this))->getName() . '::desc',
            function() 
            {
                return new ModelDescription($this);
            }
        );
    }
    
    public function count()
    {
        if ($this->dataSet) {
            return count($this->getData());
        } else {
            return $this->__call('count', []);
        }
    }

    private function retrieveItem($key)
    {
        $relationships = $this->getDescription()->getRelationships();
        if(isset($relationships[$key])) {
            return $this->fetchRelatedFields($relationships[$key]);
        } else {
            return isset($this->modelData[$key]) ? $this->modelData[$key] : null;
        }
    }

    /**
     * Create a new instance of this Model
     * @return \ntentan\nibii\RecordWrapper
     */
    public static function createNew()
    {
        $class = get_called_class();
        return InjectionContainer::resolve($class);
    }

    /**
     * @method
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public function __call($name, $arguments)
    {
        if($this->dynamicOperations === null) {
            $this->dynamicOperations = new Operations(
                $this, $this->adapter
            );
        }
        return $this->dynamicOperations->perform($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::createNew(), $name], $arguments);
    }

    public function __set($name, $value)
    {
        $this->dataSet = true;
        $this->modelData[Text::deCamelize($name)] = $value;
    }

    public function __get($name)
    {
        return $this->retrieveItem(Text::deCamelize($name));
    }

    public function getTable()
    {
        return $this->table;
    }
    
    private function expandArrayValue($array, $relationships, $depth, $index = null)
    {
        foreach($relationships as $name => $relationship) {
            $array[$name] = $this->fetchRelatedFields($relationship, $index)->toArray($depth);
        }
        return $array;
    }

    public function toArray($depth = 0)
    {
        $relationships = $this->getDescription()->getRelationships();
        $array = $this->modelData;
        if($depth > 0) {
            if($this->hasMultipleData()) {
                foreach($array as $i => $value) {
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
        $return = $this->__call('save', [$this->hasMultipleData()]);
        $this->invalidFields = $this->dynamicOperations->getInvalidFields();
        return $return;
    }

    private function hasMultipleData()
    {
        if(count($this->modelData) > 0) {
            return is_numeric(array_keys($this->modelData)[0]);
        } else {
            return false;
        }
    }

    public function getData()
    {
        $data = [];
                
        if(count($this->modelData) == 0) {
            $data = $this->modelData;
        } else if($this->hasMultipleData()) {
            $data = $this->modelData;
        } else if(count($this->modelData) > 0) {
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
        foreach($data as $key => $value) {
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
        if(isset($this->modelData[$offset])) {
            $newInstance = $this->createNew();
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
        return isset($this->modelData[$this->index]);
    }
    
    public function onValidate()
    {
        return true;
    }

    private function fetchRelatedFields($relationship, $index = null)
    {
        if($index === null) {
            $data = $this->modelData;
        } else {
            $data = $this->modelData[$index];
        }
        $model = $relationship->getModelInstance();
        if(empty($data)) {
            return $model;
        } else {
            return $model->fetch($relationship->getQuery($data));
        }
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
    
    public function getBehaviours()
    {
        return $this->loadedComponents;
    }
}
