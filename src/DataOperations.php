<?php

namespace ntentan\nibii;

use ntentan\atiaa\Driver;
use ntentan\atiaa\exceptions\ConnectionException;
use ntentan\utils\exceptions\ValidatorNotFoundException;
use ntentan\utils\Validator;

/**
 * Description of DataOperations.
 *
 * @author ekow
 */
class DataOperations
{
    /**
     * @var RecordWrapper
     */
    private $wrapper;

    /**
     * Private instance of driver adapter.
     *
     * @var DriverAdapter
     */
    private $adapter;

    /**
     * Copy of data to be manipulated in the operations.
     *
     * @var array
     */
    private $data;

    /**
     * Fields that contained errors after save or update operations were performed.
     *
     * @var array
     */
    private $invalidFields = [];

    /**
     * Set to true when the model holds multiple records.
     *
     * @var bool
     */
    private $hasMultipleData;

    /**
     * An instance of the atiaa driver.
     *
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
     * Create a new instance.
     *
     * @param \ntentan\nibii\RecordWrapper $wrapper
     * @param Driver $driver
     * @throws ConnectionException
     * @throws \ReflectionException
     * @throws exceptions\NibiiException
     */
    public function __construct(RecordWrapper $wrapper, Driver $driver)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $this->driver = $driver;
    }

    /**
     * Perform the model save command.
     *
     * @param bool $hasMultipleData
     *
     * @return bool
     * @throws \ReflectionException
     * @throws ConnectionException
     * @throws exceptions\NibiiException
     * @throws ValidatorNotFoundException
     */
    private function performSaveOperation(string $operation, bool $hasMultipleData): bool
    {
        $this->hasMultipleData = $hasMultipleData;
        $invalidFields = [];
        $data = $this->wrapper->getData();

        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $successful = true;

        // Assign an empty array to force a validation error for empty models
        if (empty($data)) {
            $data = [[]];
        } else if (!$hasMultipleData) {
            $data = [$data];
        }

        $this->driver->beginTransaction();

        foreach ($data as $i => $datum) {
            $status = $this->saveRecord($operation, $datum, $primaryKey);
            $data[$i] = $datum;

            if (!$status['success']) {
                $successful = false;
                $invalidFields[$i] = $status['invalid_fields'];
                $this->driver->rollback();
                break;
            }
        }

        if ($successful) {
            $this->driver->commit();
        } else {
            $this->assignValue($this->invalidFields, $invalidFields);
        }

        $this->wrapper->setData($hasMultipleData ? $data : $data[0]);
        return $successful;
    }
    
    public function doSave(bool $hasMultipleData): bool
    {
        return $this->performSaveOperation("save", $hasMultipleData);
    }

    public function doAdd(bool $hasMultipleData): bool
    {
        return $this->performSaveOperation("add", $hasMultipleData);
    }

    public function doUpdate(bool $hasMultipleData): bool
    {
        return $this->performSaveOperation("update", $hasMultipleData);
    }

    /**
     * @return bool|array
     * @throws \ReflectionException
     * @throws ConnectionException
     * @throws ValidatorNotFoundException
     * @throws exceptions\NibiiException
     */
    public function doValidate()
    {
        $record = $this->wrapper->getData()[0];
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $pkSet = $this->isPrimaryKeySet($primaryKey, $record);

        return $this->validate($pkSet ? self::MODE_UPDATE : self::MODE_SAVE);
    }

    /**
     * Save an individual record.
     *
     * @param array $record The record to be saved
     * @param array $primaryKey The primary keys of the record
     *
     * @return array
     * @throws \ReflectionException
     * @throws ConnectionException
     * @throws ValidatorNotFoundException
     * @throws exceptions\NibiiException
     */
    private function saveRecord(string $operation, array &$record, array $primaryKey): array
    {
        $status = [
            'success'        => true,
            'pk_assigned'    => null,
            'invalid_fields' => [],
        ];

        // Reset the data in the model to contain only the data to be saved
        $this->wrapper->setData($record);
        
        // Determine if the primary key of the record is set.
        $pkSet = $this->isPrimaryKeySet($primaryKey, $record);

        // Execute all callbacks on the model
        $this->wrapper->preSaveCallback();
        match ($operation) {
            "add" => $this->wrapper->preCreateCallback(),
            "update" => $this->wrapper->preUpdateCallback(),
            "save" => $pkSet ? $this->wrapper->preUpdateCallback() : $this->wrapper->preSaveCallback()
        };

        // Validate the data
        $validity = $this->validate(
            match ($operation) {
                "add" => self::MODE_SAVE, 
                "update" => self::MODE_UPDATE, 
                "save" => $pkSet ?  self::MODE_UPDATE : self::MODE_SAVE    
            }
        ); 

        // Exit if data is invalid
        if ($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;

            return $status;
        }
        $record = $this->wrapper->getData();

        // Save any relationships that are attached to the data
        $relationships = $this->wrapper->getDescription()->getRelationships();
        $relationshipsWithData = [];

        foreach ($relationships ?? [] as $model => $relationship) {
            if (isset($record[$model])) {
                $relationship->runSetup();
                $relationship->preSave($record, $record[$model]);
                $relationshipsWithData[$model] = $relationship;
            }
        }

        // Assign the data to the wrapper again
        $this->wrapper->setData($record);
        match ($operation) {
            "add" => [
                    $this->adapter->insert($record),
                    $keyValue = $pkSet ? $record[$primaryKey[0]] : $this->driver->getLastInsertId(),
                    $this->wrapper->{$primaryKey[0]} = $keyValue,
                    $this->wrapper->postCreateCallback($keyValue)
                ],
            "update" => [
                    $this->adapter->update($record),
                    $this->wrapper->postUpdateCallback()
                ],
            "save" => $pkSet ? [
                    $this->adapter->update($record),
                    $this->wrapper->postUpdateCallback()
                ] : [
                    $this->adapter->insert($record),
                    $keyValue = $pkSet ? $record[$primaryKey[0]] : $this->driver->getLastInsertId(),
                    $this->wrapper->{$primaryKey[0]} = $keyValue,
                    $this->wrapper->postCreateCallback($keyValue)
                ]
        };
        $this->wrapper->postSaveCallback();

        // Reset the data so it contains any modifications made by callbacks
        $record = $this->wrapper->getData();
        foreach ($relationshipsWithData as $model => $relationship) {
            $relationship->postSave($record);
            $invalidRelatedFields = $relationship->getInvalidFields();
            if(!empty($invalidRelatedFields)) {
                $status['success'] = false;
                $status['invalid_fields'][$model] = $invalidRelatedFields;
            }
        }

        return $status;
    }

    /**
     * @param int $mode
     *
     * @return bool|array
     * @throws ValidatorNotFoundException
     * @throws exceptions\NibiiException
     */
    private function validate(int $mode)
    {
        $validator = ORMContext::getInstance()->getModelValidatorFactory()->createModelValidator($this->wrapper, $mode);
        $mainValidatorErrors = [];
        $modelValidatorErrors = [];

        $data = $this->wrapper->toArray();

        if (!$validator->validate($data)) {
            $mainValidatorErrors = $validator->getInvalidFields();
        }

        if(!empty($this->wrapper->getValidationRules())) {
            $modelValidator = new Validator();
            $modelValidator->setRules($this->wrapper->getValidationRules());
            if(!$modelValidator->validate($data)) {
                $modelValidatorErrors = $modelValidator->getInvalidFields();
            }
        }

        $customValidatorErrors = $this->wrapper->onValidate($mainValidatorErrors);
        $errors = array_merge_recursive($mainValidatorErrors, $customValidatorErrors, $modelValidatorErrors);

        return empty($errors) ? true : $errors;
    }

    /**
     * @param string|array $primaryKey
     * @param array        $data
     *
     * @return bool
     */
    private function isPrimaryKeySet(string|array $primaryKey, array $data) : bool
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
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getInvalidFields() : array
    {
        return $this->invalidFields;
    }

    /**
     * @param string $primaryKey
     * @param array  $data
     *
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
