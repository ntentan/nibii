<?php

namespace ntentan\nibii;

use ntentan\utils\Text;

/**
 * A DriverAdaptr is a generic database adapter.
 * This adapter implements a lot of its operations through the atiaa library.
 * Driver specific implementation of this class only handle the conversion of
 * data types from the native datatypes of the database to the generic types
 * used in the nibii library.
 */
abstract class DriverAdapter
{

    protected $settings;
    protected $data;
    private static $defaultSettings;
    private static $defaultInstance = null;
    private $insertQuery;
    private $updateQuery;
    private $modelInstance;

    /**
     * An instance of an atiaa driver.
     * @var \ntentan\atiaa\Driver
     */
    private static $db;
    protected $queryEngine;

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function setData($data)
    {
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
     */
    public function init()
    {
        if(self::$db == null) {
            $this->settings['driver'] = $this->settings['datastore'];
            unset($this->settings['datastore']);
            self::$db = \ntentan\atiaa\Driver::getConnection($this->settings);
            self::$db->setCleanDefaults(true);

            try {
                self::$db->getPDO()->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
            } catch (\PDOException $e) {
                // Just do nothing for drivers which do not allow turning off autocommit
            }
        }
    }

    /**
     * 
     * 
     * @param type $parameters
     * @return type
     */
    public function select($parameters)
    {
        $result = self::$db->query(
            $this->getQueryEngine()->getSelectQuery($parameters), 
            $parameters->getBoundData()
        );
        
        if ($parameters->getFirstOnly() && isset($result[0])) {
            $result = $result[0];
        }

        return $result;
    }
    
    public function count($parameters)
    {
        $result = self::$db->query(
            $this->getQueryEngine()->getCountQuery($parameters),
            $parameters->getBoundData()
        );
        return $result[0]['count'];
    }

    private function initInsert()
    {
        $this->insertQuery = $this->getQueryEngine()
            ->getInsertQuery($this->modelInstance);
    }

    private function initUpdate()
    {
        $this->updateQuery = $this->getQueryEngine()
            ->getUpdateQuery($this->modelInstance);
    }

    public function insert($record)
    {
        if($this->insertQuery === null) {
            $this->initInsert();
        }
        return self::$db->query($this->insertQuery, $record);
    }

    public function update($record)
    {
        if($this->updateQuery === null) {
            $this->initUpdate();
        }
        return self::$db->query($this->updateQuery, $record);
    }

    public function bulkUpdate($data, $parameters)
    {
        return self::$db->query(
            $this->getQueryEngine()->getBulkUpdateQuery($data, $parameters),
            array_merge($data, $parameters->getBoundData())
        );
    }

    public function delete($parameters)
    {
        return self::$db->query(
            $this->getQueryEngine()->getDeleteQuery($parameters),
            $parameters->getBoundData()
        );
    }

    public function describe($model, $relationships)
    {
        return new ModelDescription(
            $this->getDriver()->describeTable($table)[$table],
            $relationships, function($type) { return $this->mapDataTypes($type); }
        );
    }

    /**
     * Set the settings used for creating default datastores.
     * @param array $settings
     */
    public static function setDefaultSettings($settings)
    {
        self::$defaultSettings = $settings;
    }

    public static function getDefaultInstance()
    {
        if (self::$defaultSettings['datastore']) {
            $class = "\\ntentan\\nibii\\adapters\\" . Text::ucamelize(self::$defaultSettings['datastore']) . "Adapter";
            self::$defaultInstance = new $class();
            self::$defaultInstance->setSettings(self::$defaultSettings);
            self::$defaultInstance->init();
        } else {
            throw new \Exception("No datastore specified");
        }
        return self::$defaultInstance;
    }

    /**
     *
     * @return \ntentan\nibii\QueryEngine
     */
    private function getQueryEngine()
    {
        if ($this->queryEngine === null) {
            $this->queryEngine = new QueryEngine();
            $this->queryEngine->setDriver(self::$db);
        }
        return $this->queryEngine;
    }

    /**
     *
     * @return \ntentan\atiaa\Driver
     */
    public function getDriver()
    {
        return self::$db;
    }

    public static function reset()
    {
        if (self::$defaultInstance !== null) {
            self::$defaultInstance->getDriver()->disconnect();
        }
        self::$defaultInstance = null;
    }

    public function setModel($model)
    {
        $this->modelInstance = $model;
    }
}
