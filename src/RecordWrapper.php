<?php

namespace ntentan\nibii;

use ntentan\utils\Utils;

class RecordWrapper implements \ArrayAccess, \Countable
{

    protected $table;
    private $description;
    private $data = [];
    private $invalidFields;
    private $dynamicOperations;
    private $validator;

    public function __construct()
    {
        Utils::factory(
            $this->table, function() {
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
        if ($this->description === null) {
            $this->description = $this->getDataAdapter()->describe($this->table);
        }
        return $this->description;
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
        $primaryKey = $this->getDescription()['primary_key'];
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
        return $this->data[$name];
    }

    public function getTable()
    {
        return $this->table;
    }

    public function toArray()
    {
        return $this->data;
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
            return $this->data[$offset];
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

    public function count($mode = 'COUNT_NORMAL')
    {
        if (array_keys($this->data)[0] === 0) {
            return count($this->data);
        } else {
            return 1;
        }
    }

    private function wrap($offset)
    {
        $newInstance = clone $this;
        $newInstance->setData($this->data[$offset]);
        return $newInstance;
    }
    
    public function getInvalidFields() 
    {
        return $this->invalidFields;
    }   
}
