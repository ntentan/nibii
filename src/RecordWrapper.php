<?php

namespace ntentan\nibii;

use ntentan\utils\Text;

class RecordWrapper implements \ArrayAccess, \Countable
{

    protected $table;
    private $description;
    private $data = [];
    private $queryParameters;
    private $invalidFields;

    public function __construct()
    {
        if ($this->table === null) {
            $this->table = $this->getDefaultTable();
        }
    }

    protected function getDefaultTable()
    {
        $class = new \ReflectionClass($this);
        $nameParts = explode("\\", $class->getName());
        return \ntentan\utils\Text::deCamelize(end($nameParts));
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
    
    protected function getValidator()
    {
        $description = $this->getDescription();
        $validator = \ntentan\utils\Validator::getInstance();
        $pk = null;
        $rules = [];
        
        
        if($description['auto_primary_key']) {
            $pk = $description['primary_key'][0];
        }
        
        foreach($description['fields'] as $name => $field) {
            $fieldRules = [];
            if($field['required'] && $name != $pk && $field['default'] === null) {
                $fieldRules[] = 'required';
            }
            if($field['type'] === 'integer' || $field['type'] === 'double') {
                $fieldRules[] = 'numeric';
            }
            $rules[$name] = $fieldRules;
        }
        
        $validator->setRules($rules);
        return $validator;
    }

    public static function createNew()
    {
        $class = get_called_class();
        return new $class();
    }
    
    public function validate()
    {
        $valid = true;
        $validator = $this->getValidator();
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
    
    public function doUpdate($data)
    {
        $instance = isset($this) ? $this : self::getInstance();
        $instance->getDriver()->beginTransaction();
        $parameters = $instance->getQueryParameters();
        $instance->getDataAdapter()->bulkUpdate($data, $parameters);
        $instance->getDriver()->commit();
    }

    private static function getInstance()
    {
        $class = get_called_class();
        return new $class();
    }

    private function doFetch($id = null)
    {
        $instance = isset($this) ? $this : self::getInstance();
        $adapter = $instance->getDataAdapter();
        $parameters = $instance->getQueryParameters();
        if ($id !== null) {
            $description = $instance->getDescription();
            $parameters->addFilter($description['primary_key'][0], [$id]);
            $parameters->setFirstOnly(true);
        }
        $instance->data = $adapter->select($parameters);
        return $instance;
    }
    
    private function deleteItem($primaryKey, $data)
    {   
        if($this->isPrimaryKeySet($primaryKey, $data)) {
            return true;
        } else {
            return false;
        }
    }
    
    private function doDelete()
    {
        $instance = isset($this) ? $this : self::getInstance();
        $instance->getDriver()->beginTransaction();
        $parameters = $instance->getQueryParameters(false);
        
        if($parameters === null) {
            $primaryKey = $this->getDescription()['primary_key'];
            $parameters = $instance->getQueryParameters();
            $data = $this->getData();
            $keys = [];
            
            foreach($data as $datum) {
                if($this->deleteItem($primaryKey, $datum)) {
                    $keys[] = $datum[$primaryKey];
                }
            }
            
            $parameters->addFilter($primaryKey[0], $keys);
            $instance->getDataAdapter()->delete($parameters);
        } else {
            $instance->getDataAdapter()->delete($parameters);            
        }
        
        $instance->getDriver()->commit();
    }

    private function doFetchFirst()
    {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch();
    }

    private function doFilter()
    {
        $arguments = func_get_args();
        if (count($arguments) == 2 && is_array($arguments[1])) {
            $filter = $arguments[0];
            $bind = $arguments[1];
        } else {
            $filter = array_shift($arguments);
            $bind = $arguments;
        }
        $filterCompiler = new FilterCompiler();
        $this->getQueryParameters()->setRawFilter(
            $filterCompiler->compile($filter), 
            $filterCompiler->rewriteBoundData($bind)
        );
        return $this;
    }

    private function doFields()
    {
        $arguments = func_get_args();
        $this->getQueryParameters()->setFields($arguments);
        return $this;
    }

    public function __call($name, $arguments)
    {
        if (array_search($name, ['fetch', 'fetchFirst', 'filter', 'fields', 'update', 'delete']) !== false) {
            $method = "do{$name}";
            return call_user_func_array([$this, $method], $arguments);
        } else if (preg_match("/(filterBy)(?<variable>[A-Za-z]+)/", $name, $matches)) {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
            return $this;
        } else if (preg_match("/(fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/", $name, $matches)) {
            $parameters = $this->getQueryParameters();
            $parameters->addFilter(Text::deCamelize($matches['variable']), $arguments);
            if ($matches['first'] === 'First') {
                $parameters->setFirstOnly(true);
            }
            return $this->doFetch();
        } else {
            return call_user_func_array([$this->getDataAdapter(), $name], $arguments);
        }
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
    
    /**
     * 
     * @return \ntentan\nibii\QueryParameters
     */
    private function getQueryParameters($instantiate = true)
    {
        if ($this->queryParameters === null && $instantiate) {
            $this->queryParameters = new QueryParameters($this->getDriver(), $this->table);
        }
        return $this->queryParameters;
    }    
}
