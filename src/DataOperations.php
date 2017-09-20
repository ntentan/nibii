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

use ntentan\atiaa\Driver;

/**
 * Description of DataOperations
 *
 * @author ekow
 */
class DataOperations
{

    private $wrapper;

    /**
     * Private instance of driver adapter
     *
     * @var DriverAdapter
     */
    private $adapter;

    /**
     * Copy of data to be manipulated in the operations.
     * @var array
     */
    private $data;

    /**
     * Fields that contained errors after save or update operations were performed.
     * @var array
     */
    private $invalidFields = [];

    /**
     * Set to true when the model holds multiple records.
     * @var bool
     */
    private $hasMultipleData;

    /**
     * An instance of the atiaa driver.
     * @var Driver
     */
    private $driver;

    /**
     * Used to indicate save operation is in save mode to create new items.
     */
    const MODE_SAVE = 0;
    
    /**
     * Used to indicate save operation is in update mode to update existing items.
     */
    const MODE_UPDATE = 1;

    /**
     * 
     * @param \ntentan\nibii\RecordWrapper $wrapper
     * @param Driver $driver
     */
    public function __construct(RecordWrapper $wrapper, Driver $driver)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $this->driver = $driver;
    }

    /**
     * 
     * @param bool $hasMultipleData
     * @return bool
     */
    public function doSave(bool $hasMultipleData): bool
    {
        $this->hasMultipleData = $hasMultipleData;
        $invalidFields = [];
        $data = $this->wrapper->getData();

        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $succesful = true;

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

    /**
     * 
     * @return bool|array
     */
    public function doValidate()
    {
        $record = $this->wrapper->getData()[0];
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $pkSet = $this->isPrimaryKeySet($primaryKey, $record);
        return $this->validate($record, $pkSet ? DataOperations::MODE_UPDATE : DataOperations::MODE_SAVE);
    }

    /**
     * Save an individual record.
     * 
     * @param array $record The record to be saved
     * @param array $primaryKey The primary keys of the record
     * @return array
     */
    private function saveRecord(array &$record, array $primaryKey): array
    {
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
        $validity = $this->validate($record, $pkSet ? DataOperations::MODE_UPDATE : DataOperations::MODE_SAVE);

        // Exit if data is invalid
        if ($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }

        // Save any relationships that are attached to the data
        $relationships = $this->wrapper->getDescription()->getRelationships();
        $presentRelationships = [];

        foreach ($relationships ?? [] as $model => $relationship) {
            if (isset($record[$model])) {
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
        foreach ($presentRelationships as $model => $relationship) {
            $relationship->postSave($record);
        }

        return $status;
    }

    /**
     * 
     * @param array $data
     * @param type $mode
     * @return bool|array
     */
    private function validate(array $data, int $mode)
    {
        $validator = ORMContext::getInstance()->getModelValidatorFactory()->createModelValidator($this->wrapper, $mode);
        $errors = [];

        if (!$validator->validate($data)) {
            $errors = $validator->getInvalidFields();
        }
        $errors = $this->wrapper->onValidate($errors);
        return empty($errors) ? true : $errors;
    }

    /**
     * 
     * @param string|array $primaryKey
     * @param array $data
     * @return bool
     */
    private function isPrimaryKeySet($primaryKey, array $data) : bool
    {
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
    
    /**
     * 
     * @param mixed $property
     * @param mixed $value
     */
    private function assignValue(&$property, $value) : void
    {
        if ($this->hasMultipleData) {
            $property = $value;
        } else {
            $property = $value[0];
        }
    }

    /**
     * 
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * 
     * @return array
     */
    public function getInvalidFields() : array
    {
        return $this->invalidFields;
    }

    /**
     * 
     * @param string $primaryKey
     * @param array $data
     * @return bool
     */
    public function isItemDeletable(string $primaryKey, array $data) : bool
    {
        if ($this->isPrimaryKeySet($primaryKey, $data)) {
            return true;
        } else {
            return false;
        }
    }

}
