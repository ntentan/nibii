<?php

namespace ntentan\nibii;

class ModelDescription
{
    private $fields = [];
    private $primaryKey = [];
    private $uniqueKeys = [];
    private $autoPrimaryKey = false;
    private $relationships = [];

    public function __construct($schema, $relationships, $typeMapper)
    {
        $this->autoPrimaryKey = $schema['auto_increment'];
        foreach ($schema['columns'] as $field => $details) {
            $this->fields[$field] = [
                'type' => $typeMapper($details['type']),
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

        foreach($relationships as $type => $relations) {
            $this->createRelationships($type, $relations);
        }

        $this->appendConstraints($schema['primary_key'], $this->primaryKey, true);
        $this->appendConstraints($schema['unique_keys'], $this->uniqueKeys);
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
        }
    }

    private function createRelationships($type, $relationships)
    {
        foreach($relationships as $relationship)
        {
            $relationship = $this->getRelationshipDetails($relationship);
            $class = "\\ntentan\\nibii\\relationships\\{$type}Relationship";
            $relationshipObject = new $class();
            $relationshipObject->setForeignKey($relationship['foreign_key']);
            $relationshipObject->setLocalKey($relationship['local_key']);
            $relationshipObject->setModel($relationship['model']);
            $relationshipObject->setup();
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
}
