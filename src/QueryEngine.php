<?php

namespace ntentan\nibii;

class QueryEngine
{

    private $db;

    public function setDriver($driver)
    {
        $this->db = $driver;
    }

    public function insert($model)
    {
        $data = $model->getData();
        $fields = array_keys($data[0]);
        $quotedFields = [];
        $valueFields = [];
        
        foreach ($fields as $field) {
            $quotedFields[] = $this->db->quoteIdentifier($field);
            $valueFields[] = ":{$field}";
        }
        
        return "INSERT INTO " . $this->db->quoteIdentifier($model->getTable()) .
            " (" . implode(", ", $quotedFields) . ") VALUES (" . implode(', ', $valueFields) . ")";
    }
    
    public function bulkUpdate($data, $parameters)
    {
        $updateData = [];
        foreach($data as $field => $value) {
            $updateData[] = "{$this->db->quoteIdentifier($field)} = :$field";
        }
        
        return sprintf(
            "UPDATE %s SET %s %s",
            $parameters->getTable(),
            implode(', ', $updateData),
            $parameters->getWhereClause()
        );
    }

    public function update($model)
    {
        $data = $model->getData();
        $fields = array_keys($data[0]);
        $valueFields = [];
        $conditions = [];
        $primaryKey = $model->getDescription()['primary_key'];
        
        foreach ($fields as $field) {
            $quotedField = $this->db->quoteIdentifier($field);
            
            if(array_search($field, $primaryKey) !== false) {
                $conditions[] = "{$quotedField} = :{$field}";
            } else {
                $valueFields[] = "{$quotedField} = :{$field}";
            }
        }
        
        return "UPDATE " . 
            $this->db->quoteIdentifier($model->getTable()) . 
            " SET " . implode(', ', $valueFields) .
            " WHERE " . implode(' AND ', $conditions);
    }

    public function select($parameters)
    {   
        return sprintf(
            "SELECT %s FROM %s%s", 
            $parameters->getFields(), 
            $parameters->getTable(), 
            $parameters->getWhereClause()
        );
    }
    
    public function delete($parameters)
    {
        return sprintf(
            "DELETE FROM %s%s",
            $parameters->getTable(),
            $parameters->getWhereClause()
        );
    }
}
