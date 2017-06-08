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

/**
 * Description of DataOperations
 *
 * @author ekow
 */
class DataOperations {

    private $wrapper;

    /**
     *
     * @var DriverAdapter
     */
    private $adapter;
    private $data;
    private $invalidFields;
    private $hasMultipleData;
    private $driver;
    private $container;

    const MODE_SAVE = 0;
    const MODE_UPDATE = 1;

    public function __construct(ORMContext $context, RecordWrapper $wrapper, DriverAdapter $adapter) {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
        $this->driver = $context->getDbContext()->getDriver();
        $this->container = $context->getContainer();
    }

    public function doSave($hasMultipleData) {
        $this->hasMultipleData = $hasMultipleData;
        $invalidFields = [];
        $data = $this->wrapper->getData();
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $singlePrimaryKey = null;
        $succesful = true;

        if (count($primaryKey) == 1) {
            $singlePrimaryKey = $primaryKey[0];
        }

        // Assign an empty array to force a validation error for empty models
        if (empty($data)) {
            $data = [[]];
        }

        $this->driver->beginTransaction();

        foreach ($data as $i => $datum) {
            $status = $this->saveRecord($datum, $primaryKey);
            $data[$i] = $datum;

            if (!$status['success']) {
                $succesful = false;
                $invalidFields[$i] = $status['invalid_fields'];
                $this->driver->rollback();
                break;
            }
        }

        if ($succesful) {
            $this->driver->commit();
        } else {
            $this->assignValue($this->invalidFields, $invalidFields);
        }

        $this->wrapper->setData($hasMultipleData ? $data : $data[0]);

        return $succesful;
    }
    
    public function doValidate() {
        $record = $this->wrapper->getData()[0];
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $pkSet = $this->isPrimaryKeySet($primaryKey, $record);
        return $this->validate(
            $record, $pkSet ? DataOperations::MODE_UPDATE : DataOperations::MODE_SAVE
        );
    }

    /**
     * Save an individual record.
     * 
     * @param array $record The record to be saved
     * @param type $primaryKey The primary keys of the record
     * @return boolean
     */
    private function saveRecord(&$record, $primaryKey) {
        $status = [
            'success' => true,
            'pk_assigned' => null,
            'invalid_fields' => []
        ];

        // Determine if the primary key of the record is set.
        $pkSet = $this->isPrimaryKeySet($primaryKey, $record);

        // Reset the data in the model to contain only the data to be saved
        $this->wrapper->setData($record);

        // Run preUpdate or preSave callbacks on models and behaviours
        if ($pkSet) {
            $this->wrapper->preUpdateCallback();
            $record = $this->wrapper->getData();
            $record = reset($record) === false ? [] : reset($record);
        } else {
            $this->wrapper->preSaveCallback();
            $record = $this->wrapper->getData();
            $record = reset($record) === false ? [] : reset($record);
        }

        // Validate the data
        $validity = $this->validate(
            $record, $pkSet ? DataOperations::MODE_UPDATE : DataOperations::MODE_SAVE
        );

        // Exit if data is invalid
        if ($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }
        
        // Save any relationships that are attached to the data
        $relationships = $this->wrapper->getDescription()->getRelationships();
        $presentRelationships = [];
        
        foreach($relationships ?? [] as $model => $relationship) {
            if(isset($record[$model])) {
                $relationship->preSave($record, $record[$model]);
                $presentRelationships[$model] = $relationship;
            }
        }
        
        // Assign the data to the wrapper again
        $this->wrapper->setData($record);

        // Update or save the data and run post callbacks
        if ($pkSet) {
            $this->adapter->update($record);
            $this->wrapper->postUpdateCallback();
        } else {
            $this->adapter->insert($record);
            $keyValue = $this->driver->getLastInsertId();
            $this->wrapper->{$primaryKey[0]} = $keyValue;
            $this->wrapper->postSaveCallback($keyValue);
        }
        
        // Reset the data so it contains any modifications made by callbacks
        $record = $this->wrapper->getData()[0];
        foreach($presentRelationships as $model => $relationship) {
            $relationship->postSave($record);
        }        

        return $status;
    }

    private function validate($data, $mode) {
        $valid = true;
        $validator = $this->container->resolve(
            ModelValidator::class, ['model' => $this->wrapper, 'mode' => $mode]
        );

        if (!$validator->validate($data)) {
            $valid = false;
        }

        if ($valid) {
            $valid = $this->wrapper->onValidate();
        } else {
            $valid = $validator->getInvalidFields();
        }

        return $valid;
    }

    private function isPrimaryKeySet($primaryKey, $data) {
        if (is_string($primaryKey) && ($data[$primaryKey] !== null || $data[$primaryKey] !== '')) {
            return true;
        }
        foreach ($primaryKey as $keyField) {
            if (!isset($data[$keyField]) || $data[$keyField] === null || $data[$keyField] === '') {
                return false;
            }
        }
        return true;
    }

    private function assignValue(&$property, $value) {
        if ($this->hasMultipleData) {
            $property = $value;
        } else {
            $property = $value[0];
        }
    }

    public function getData() {
        return $this->data;
    }

    public function getInvalidFields() {
        return $this->invalidFields;
    }

    public function isItemDeletable($primaryKey, $data) {
        if ($this->isPrimaryKeySet($primaryKey, $data)) {
            return true;
        } else {
            return false;
        }
    }

}
