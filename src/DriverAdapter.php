<?php
namespace ntentan\nibii;

use ntentan\utils\Text;

abstract class DriverAdapter
{
    protected $settings;
    protected $data;
    private static $defaultSettings;
    private static $defaultInstance;
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
        $this->db = \ntentan\atiaa\Driver::getConnection($this->settings);
    }
    
    public function select($parameters)
    {
        $result = $this->db->query(
            $this->getQueryEngine()->select($parameters), 
            $parameters->getBoundData()
        );
        
        if($parameters->getFirstOnly())
        {
            $result = reset($result);
        }
        
        return $result;
    }
    
    public function insert($record)
    {
        return $this->db->query(
            $this->getQueryEngine()->insert($record), 
            array_values($record->getData())
        );
    }
    
    public function describe($table) 
    {
        $schema = reset($this->db->describeTable($table));
        $description = [
            'fields' => [],
            'primary_key' => [],
            'unique_keys' => []
        ];
        foreach($schema['columns'] as $field => $details)
        {
            $description['fields'][$field] = [
                'type' => $this->mapDataTypes($details['type']),
                'required' => !$details['nulls'],
                'name' => $field
            ];
            
            $this->appendConstraints($description, $schema['primary_key'], 'primary_key', true);
            $this->appendConstraints($description, $schema['unique_keys'], 'unique');
        }
        return $description;
    }
    
    private function appendConstraints(&$description, $constraints, $key, $flat = false)
    {
        foreach($constraints as $constraint)
        {
            if($flat)
            {
                $description[$key] = $constraint['columns'];
                break;
            }
            else
            {
                $description[$key][] = array(
                    'fields' =>$constraint['columns']
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
        if(self::$defaultInstance === null)
        {
            if(self::$defaultSettings['datastore'])
            {
                $class = "\\ntentan\\nibii\\adapters\\" . Text::ucamelize(self::$defaultSettings['datastore']) . "Adapter";
                self::$defaultInstance = new $class();
                self::$defaultInstance->setSettings(self::$defaultSettings);
                self::$defaultInstance->init();
            }
            else
            {
                throw new \Exception("No datastore specified");
            }
        }
        return self::$defaultInstance;
    }
    
    private function getQueryEngine()
    {
        if($this->queryEngine === null)
        {
            $this->queryEngine = new QueryEngine();
            $this->queryEngine->setDriver($this->db);
        }
        return $this->queryEngine;
    }
    
    public function getDriver()
    {
        return $this->db;
    }
}
