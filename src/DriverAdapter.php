<?php

namespace ntentan\nibii;

use ntentan\utils\Text;
use ntentan\atiaa\Db;

/**
 * A DriverAdaptr is a generic database adapter.
 * This adapter implements a lot of its operations through the atiaa library.
 * Driver specific implementation of this class only handle the conversion of
 * data types from the native datatypes of the database to the generic types
 * used in the nibii library.
 */
abstract class DriverAdapter {

    protected $data;
    private $insertQuery;
    private $updateQuery;
    private $modelInstance;
    protected $queryEngine;

    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Convert datatypes from the database system's native type to a generic type
     * supported by nibii.
     *
     * @param string $nativeType The native datatype
     * @return string The generic datatype for use in nibii.
     */
    abstract public function mapDataTypes($nativeType);

    /**
     * 
     * 
     * @param type $parameters
     * @return type
     */
    public function select($parameters) {
        $result = Db::getDriver()->query(
            $this->getQueryEngine()->getSelectQuery($parameters), 
            $parameters->getBoundData()
        );

        if ($parameters->getFirstOnly() && isset($result[0])) {
            $result = $result[0];
        }

        return $result;
    }

    public function count($parameters) {
        $result = Db::getDriver()->query(
                $this->getQueryEngine()->getCountQuery($parameters), $parameters->getBoundData()
        );
        return $result[0]['count'];
    }

    private function initInsert() {
        $this->insertQuery = $this->getQueryEngine()
                ->getInsertQuery($this->modelInstance);
    }

    private function initUpdate() {
        $this->updateQuery = $this->getQueryEngine()->getUpdateQuery($this->modelInstance);
    }

    public function insert($record) {
        if ($this->insertQuery === null) {
            $this->initInsert();
        }
        return Db::getDriver()->query($this->insertQuery, $record);
    }

    public function update($record) {
        if ($this->updateQuery === null) {
            $this->initUpdate();
        }
        return Db::getDriver()->query($this->updateQuery, $record);
    }

    public function bulkUpdate($data, $parameters) {
        return Db::getDriver()->query(
                        $this->getQueryEngine()->getBulkUpdateQuery($data, $parameters), array_merge($data, $parameters->getBoundData())
        );
    }

    public function delete($parameters) {
        return Db::getDriver()->query(
                        $this->getQueryEngine()->getDeleteQuery($parameters), $parameters->getBoundData()
        );
    }

    public function describe($model, $relationships) {
        return new ModelDescription(
                Db::getDriver()->describeTable($table)[$table], $relationships, function($type) {
            return $this->mapDataTypes($type);
        }
        );
    }

    public static function getDefaultInstance() {
        return \ntentan\panie\InjectionContainer::resolve(DriverAdapter::class);
    }

    /**
     *
     * @return \ntentan\nibii\QueryEngine
     */
    private function getQueryEngine() {
        if ($this->queryEngine === null) {
            $this->queryEngine = new QueryEngine();
            $this->queryEngine->setDriver(Db::getDriver());
        }
        return $this->queryEngine;
    }

    public function setModel($model) {
        $this->modelInstance = $model;
    }

}
