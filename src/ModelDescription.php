<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
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

class ModelDescription
{
    private $fields = [];
    private $primaryKey = [];
    private $uniqueKeys = [];
    private $autoPrimaryKey = false;

    /**
     * @var array<Relationship>
     */
    private $relationships = [];
    private $table;
    private $schema;
    private $name;

    /**
     * @param RecordWrapper $model
     */
    public function __construct($model)
    {
        $context = ORMContext::getInstance();
        $dbInformation = $model->getDBStoreInformation();
        $this->table = $dbInformation['table'];
        $this->schema = $dbInformation['schema'];
        $this->name = $context->getModelName((new \ReflectionClass($model))->getName());
        $relationships = $model->getRelationships();
        $adapter = $model->getAdapter();
        $schema = array_values(
            $context->getDbContext()->getDriver()->describeTable(
                $dbInformation['unquoted_table']
            )
        )[0];
        $this->autoPrimaryKey = $schema['auto_increment'];

        foreach ($schema['columns'] as $field => $details) {
            $this->fields[$field] = [
                'type'     => $adapter->mapDataTypes($details['type']),
                'required' => !$details['nulls'],
                'default'  => $details['default'],
                'name'     => $field,
            ];
            if (isset($details['default'])) {
                $this->fields[$field]['default'] = $details['default'];
            }
            if (isset($details['length'])) {
                $this->fields[$field]['length'] = $details['length'];
            }
        }

        $this->appendConstraints($schema['primary_key'], $this->primaryKey, true);
        $this->appendConstraints($schema['unique_keys'], $this->uniqueKeys);

        foreach ($relationships as $type => $relations) {
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
                $key[] = [
                    'fields' => $constraint['columns'],
                ];
            }
        }
    }

    private function getRelationshipDetails($relationship)
    {
        $relationshipDetails = [];
        if (is_string($relationship)) {
            $relationshipDetails = [
                'model'       => $relationship,
                'name'        => $relationship,
                'foreign_key' => '',
                'local_key'   => '',
            ];
        } elseif (is_array($relationship)) {
            $relationshipDetails = [
                'model'       => $relationship[0],
                'name'        => isset($relationship['as']) ? $relationship['as'] : $relationship[0],
                'foreign_key' => isset($relationship['foreign_key']) ? $relationship['foreign_key'] : '',
                'local_key'   => isset($relationship['local_key']) ? $relationship['local_key'] : '',
            ];
        } else {
            return;
        }
        $relationshipDetails['local_table'] = $this->table;
        if (isset($relationship['through'])) {
            $relationshipDetails['through'] = $relationship['through'];
        }

        return $relationshipDetails;
    }

    private function createRelationships($type, $relationships)
    {
        foreach ($relationships as $relationship) {
            $relationship = $this->getRelationshipDetails($relationship);
            $class = "\\ntentan\\nibii\\relationships\\{$type}Relationship";
            $relationshipObject = new $class();
            $relationshipObject->setOptions($relationship);
            $relationshipObject->setup($this->name, $this->schema, $this->table, $this->primaryKey);
            $this->relationships[$relationship['name']] = $relationshipObject;
        }
    }

    /**
     * @return array<Relationship>
     */
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
