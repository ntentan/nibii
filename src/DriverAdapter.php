<?php

namespace ntentan\nibii;

use ntentan\utils\Text;

abstract class DriverAdapter
{

    protected $settings;
    protected $data;
    private static $defaultSettings;
    private static $defaultInstance = null;
    private $insertQuery;

    /**
     *
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
            
        }
    }

    public function select($parameters)
    {
        $result = $this->db->query(
                $this->getQueryEngine()->select($parameters), $parameters->getBoundData()
        );

        if ($parameters->getFirstOnly()) {
            $result = reset($result);
        }

        return $result;
    }

    public function initInsert($model)
    {
        $this->insertQuery = $this->getQueryEngine()->insert($model);
    }

    public function insert($record)
    {
        return $this->db->query($this->insertQuery, $record);
    }

    public function describe($table)
    {
        $schema = $this->db->describeTable($table)[$table];
        
        $description = [
            'fields' => [],
            'primary_key' => [],
            'unique_keys' => [],
            'auto_primary_key' => $schema['auto_increment']
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

        return $description;
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

}
