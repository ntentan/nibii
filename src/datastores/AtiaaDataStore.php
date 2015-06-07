<?php
namespace ntentan\nibii\datastores;

abstract class AtiaaDataStore extends \ntentan\nibii\DataStore
{
    protected $db;
    
    abstract protected function mapDataTypes($nativeType);
    
    public function init() 
    {
        $this->settings['driver'] = $this->settings['datastore'];
        $this->db = \ntentan\atiaa\Driver::getConnection($this->settings);
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
}