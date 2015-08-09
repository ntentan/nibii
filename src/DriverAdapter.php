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
    protected $db;
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
    abstract protected function mapDataTypes($nativeType);

    public function init()
    {
        $this->settings['driver'] = $this->settings['datastore'];
        unset($this->settings['datastore']);
        $this->db = \ntentan\atiaa\Driver::getConnection($this->settings);
        $this->db->setCleanDefaults(true);

        try {
            $this->db->getPDO()->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        } catch (\PDOException $e) {
            // Just do nothing for drivers which do not allow turning off autocommit
        }
    }

    public function select($parameters)
    {
        $result = $this->db->query(
            $this->getQueryEngine()->getSelectQuery($parameters), $parameters->getBoundData()
        );
        
        if ($parameters->getFirstOnly()) {
            $result = $result[0];
        }

        return $result;
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
        return $this->db->query($this->insertQuery, $record);
    }
    
    public function update($record)
    {
        if($this->updateQuery === null) {
            $this->initUpdate();
        }
        return $this->db->query($this->updateQuery, $record);
    }
    
    public function bulkUpdate($data, $parameters)
    {
        return $this->db->query(
            $this->getQueryEngine()->getBulkUpdateQuery($data, $parameters),
            array_merge($data, $parameters->getBoundData())
        );
    }
    
    public function delete($parameters)
    {
        return $this->db->query(
            $this->getQueryEngine()->getDeleteQuery($parameters),
            $parameters->getBoundData()
        );
    }

    public function describe($table, $relationships)
    {
        $schema = $this->db->describeTable($table)[$table];
        
        $description = [
            'table' => $table,
            'fields' => [],
            'primary_key' => [],
            'unique_keys' => [],
            'auto_primary_key' => $schema['auto_increment'],
            'relationships' => [],
            'has_many' => [],
            'belongs_to' => []
        ];
        
        foreach ($schema['columns'] as $field => $details) {
            $description['fields'][$field] = [
                'type' => $this->mapDataTypes($details['type']),
                'required' => !$details['nulls'],
                'default' => $details['default'],
                'name' => $field
            ];
            if(isset($details['default'])) {
                $description['fields'][$field]['default'] = $details['default'];
            }
            if(isset($details['length'])) {
                $description['fields'][$field]['length'] = $details['length'];
            }
        }
        
        $this->appendConstraints($description, $schema['primary_key'], 'primary_key', true);
        $this->appendConstraints($description, $schema['unique_keys'], 'unique');
        $this->setRelationships($description, $relationships, 'belongs_to');
        return $description;
    }
    
    private function setRelationships(&$description, $relationships, $type)
    {
        if($relationships[$type] === null) {
            return;
        }
        if(!is_array($relationships[$type])) {
            $relationships[$type] = [$relationships[$type]];
        }
        
        foreach($relationships[$type] as $key => $relationship) {
            $name = null;
            
            if(!is_numeric($key)) {
                $name = $key;
            } else if(is_string($relationship)) {
                $name = $relationship;
            }
            
            if(!is_array($relationship)) {
                $relationship = ['model' => $relationship ];
            }
            if(!isset($relationship['model'])) {
                $relationship['model'] = $name;
            }            
            
            $model = Nibii::load($relationship['model']);
            if(!isset($relationship['local_key'])) {
                switch($type) {
                    case 'belongs_to':
                        $relationship['local_key'] = Text::singularize($model->getTable()) . "_id";
                        break;
                    case 'has_many':
                        $relationship['local_key'] = $description['primary_key'][0];
                        break;
                }
            }
            if(!isset($relationship['foreign_key'])) {
                switch($type) {
                    case 'belongs_to':
                        $relationship['foreign_key'] = $model->getDescription()['primary_key'][0];
                        break;
                    case 'has_many':
                        $relationship['foreign_key'] = Text::singularize($description['table']) . "_id";
                        break;
                }
                        
                
            }
            $relationship['type'] = $type;
            $description['relationships'][$name] = $relationship;
        }
    }

    private function appendConstraints(&$description, $constraints, $key, $flat = false)
    {
        foreach ($constraints as $constraint) {
            if ($flat) {
                $description[$key] = $constraint['columns'];
                break;
            } else {
                $description[$key][] = array(
                    'fields' => $constraint['columns']
                );
            }
        }
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
        if (self::$defaultInstance === null) {
            if (self::$defaultSettings['datastore']) {
                $class = "\\ntentan\\nibii\\adapters\\" . Text::ucamelize(self::$defaultSettings['datastore']) . "Adapter";
                self::$defaultInstance = new $class();
                self::$defaultInstance->setSettings(self::$defaultSettings);
                self::$defaultInstance->init();
            } else {
                throw new \Exception("No datastore specified");
            }
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
            $this->queryEngine->setDriver($this->db);
        }
        return $this->queryEngine;
    }

    /**
     * 
     * @return \ntentan\atiaa\Driver
     */
    public function getDriver()
    {
        return $this->db;
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
