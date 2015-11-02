<?php

namespace ntentan\nibii;

class ModelDescription
{
    private $fields = [];
    private $primaryKey = [];
    private $uniqueKeys = [];
    private $autoPrimaryKey = false;
    private $relationships = [];
    private $table;
    private $name;

    public function __construct($model)
    {
        $this->table = $model->getTable();
        $this->name = Nibii::getModelName((new \ReflectionClass($model))->getName());
        $relationships = $model->getRelationships();
        $adapter = DriverAdapter::getDefaultInstance();
        $schema = $adapter->getDriver()->describeTable($this->table)[$this->table];
        $this->autoPrimaryKey = $schema['auto_increment'];

        foreach ($schema['columns'] as $field => $details) {
            $this->fields[$field] = [
                'type' => $adapter->mapDataTypes($details['type']),
                'required' => !$details['nulls'],
                'default' => $details['default'],
                'name' => $field
            ];
            if(isset($details['default'])) {
                $this->fields[$field]['default'] = $details['default'];
            }
            if(isset($details['length'])) {
                $this->fields[$field]['length'] = $details['length'];
            }
        }

        $this->appendConstraints($schema['primary_key'], $this->primaryKey, true);
        $this->appendConstraints($schema['unique_keys'], $this->uniqueKeys);

        foreach($relationships as $type => $relations) {
            $this->createRelationships($type, $relations);
        }

    }

    private function appendConstraints($constraints, &$key, $flat = false)
    {
        foreach ($constraints as $constraint) {
            if ($flat) {
                $key = $constraint['columns'];
                break;
            } else {
                $key[] = array(
                    'fields' => $constraint['columns']
                );
            }
        }
    }

    private function getRelationshipDetails($relationship)
    {
        if(is_string($relationship))
        {
            return [
                'model' => $relationship,
                'name' => $relationship,
                'foreign_key' => '',
                'local_key' => ''
            ];
        } else if (is_array($relationship)) {
            return [
                'model' => $relationship[0],
                'name' => isset($relationship['as']) ? $relationship['as'] : $relationship[0],
                'foreign_key' => isset($relationship['foreign_key']) ? $relationship['foreign_key'] : '',
                'local_key' => isset($relationship['local_key']) ? $relationship['local_key'] : ''
            ];
        }
    }

    private function createRelationships($type, $relationships)
    {
        foreach($relationships as $relationship)
        {
            $relationship = $this->getRelationshipDetails($relationship);
            $class = "\\ntentan\\nibii\\relationships\\{$type}Relationship";
            $relationshipObject = new $class();
            $relationshipObject->setOptions($relationship);
            $relationshipObject->setup($this->name, $this->table, $this->primaryKey);
            $this->relationships[$relationship['name']] = $relationshipObject;
        }
    }

    public function getRelationships()
    {
        return $this->relationships;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getAutoPrimaryKey()
    {
        return $this->autoPrimaryKey;
    }

    public function getFields()
    {
        return $this->fields;
    }
    
    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }
}
