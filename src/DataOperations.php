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
    private $adapter;
    private $data;
    private $invalidFields;
    private $queryOperations;
    private $validator;
    private $hasMultipleData;
    
    public function __construct($wrapper, $adapter, $queryOperations)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;        
        $this->queryOperations = $queryOperations;
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
        
        $this->wrapper->setData($data);

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
        } else {
            $this->wrapper->preSaveCallback();
        }
        
        $preProcessed = $this->wrapper->getData()[0];
        $validity = $this->validate($preProcessed);

        if($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }

        if($pkSet) {
            $this->adapter->update($preProcessed);
            $this->wrapper->postUpdateCallback();
        } else {
            $this->adapter->insert($preProcessed);
            $status['pk_assigned'] = $this->adapter->getDriver()->getLastInsertId();
            $this->wrapper->postSaveCallback($status['pk_assigned']);
        }

        return $status;
    }
    
    private function validate()
    {
        $valid = true;
        $validator = Utils::factory($this->validator,
            function() {
                return new ModelValidator($this->wrapper->getDescription());
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
}
