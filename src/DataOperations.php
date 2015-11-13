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

/**
 * Description of DataOperations
 *
 * @author ekow
 */
class DataOperations
{
    private $wrapper;
    
    /**
     *
     * @var DriverAdapter
     */
    private $adapter;
    private $data;
    private $invalidFields;
    private $validator;
    private $hasMultipleData;
    
    const MODE_SAVE = 0;
    const MODE_UPDATE = 1;
    
    public function __construct($wrapper, $adapter)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;        
    }
    
    public function doSave($hasMultipleData)
    {
        $this->hasMultipleData = $hasMultipleData;
        $invalidFields = [];
        $data = $this->wrapper->getData();
        $this->adapter->setModel($this->wrapper);
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $singlePrimaryKey = null;
        $succesful = true;

        if (count($primaryKey) == 1) {
            $singlePrimaryKey = $primaryKey[0];
        }
        
        // Assign an empty array to force a validation error for empty models
        if(empty($data)) {
            $data = [[]];
        }

        $this->adapter->getDriver()->beginTransaction();

        foreach($data as $i => $datum) {
            $status = $this->saveRecord($datum, $primaryKey);
            
            if(!$status['success']) {
                $succesful = false;
                $invalidFields[$i] = $status['invalid_fields'];
                $this->adapter->getDriver()->rollback();
                continue;
            }

            if($singlePrimaryKey && isset($status['pk_assigned'])) {
                $data[$i][$singlePrimaryKey] = $status['pk_assigned'];
            }
        }
        
        if($succesful) {
            $this->assignValue($this->data, $data);
            $this->adapter->getDriver()->commit();
        } else {
            $this->assignValue($this->invalidFields, $invalidFields);
        }
        
        $this->wrapper->setData($hasMultipleData ? $data : $data[0]);

        return $succesful;
    }   
    
    private function saveRecord($datum, $primaryKey)
    {
        $status = [
            'success' => true,
            'pk_assigned' => null,
            'invalid_fields' => []
        ];
        $pkSet = $this->isPrimaryKeySet($primaryKey, $datum);
        $this->wrapper->setData($datum);

        if($pkSet) {
            $this->wrapper->preUpdateCallback();
            $preProcessed = $this->wrapper->getData();
            $preProcessed = reset($preProcessed) === false ? [] : reset($preProcessed);
            $preProcessed = $this->runBehaviours('preUpdateCallback', [$preProcessed]);
        } else {
            $this->wrapper->preSaveCallback();
            $preProcessed = $this->wrapper->getData();
            $preProcessed = reset($preProcessed) === false ? [] : reset($preProcessed);
            $preProcessed = $this->runBehaviours('preSaveCallback', [$preProcessed]);
        }        
        
        $validity = $this->validate(
            $preProcessed, 
            $pkSet ? DataOperations::MODE_UPDATE : DataOperations::MODE_SAVE
        );

        if($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }
        
        $this->wrapper->setData($preProcessed);

        if($pkSet) {
            $this->adapter->update($preProcessed);
            $this->wrapper->postUpdateCallback();
            $this->runBehaviours('postUpdateCallback', [$preProcessed]);
        } else {
            $this->adapter->insert($preProcessed);
            $status['pk_assigned'] = $this->adapter->getDriver()->getLastInsertId();
            $this->wrapper->postSaveCallback($status['pk_assigned']);
            $this->runBehaviours('postUpdateCallback', [$preProcessed, $status['pk_assigned']]);
        }

        return $status;
    }
    
    private function validate($data, $mode)
    {
        $valid = true;
        $validator = Utils::factory($this->validator,
            function() use($mode) {
                return new ModelValidator($this->wrapper, $mode);
            }
        );

        if(!$validator->validate($data)) {
            $valid = false;
        }

        if($valid === false) {
            $valid = $validator->getInvalidFields();
        }

        return $valid;
    }
    

    private function isPrimaryKeySet($primaryKey, $data)
    {
        if(is_string($primaryKey)) {
            if(isset($data[$primaryKey]))
            {
                return true;
            }
        }
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
    
    private function assignValue(&$property, $value)
    {
        if($this->hasMultipleData) {
            $property = $value;
        } else {
            $property = $value[0];
        }
    }    
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getInvalidFields()
    {
        return $this->invalidFields;
    }   
    
    public function isItemDeletable($primaryKey, $data)
    {
        if($this->isPrimaryKeySet($primaryKey, $data)) {
            return true;
        } else {
            return false;
        }
    }     
    
    private function runBehaviours($event, $args)
    {
        foreach($this->wrapper->getBehaviours() as $behaviour) {
            $args[0] = call_user_func_array([$behaviour, $event], $args);
        }
        return $args[0];
    }
}
