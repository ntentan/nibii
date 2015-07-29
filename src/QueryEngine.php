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
        foreach($fields as $field)
        {
            $quotedFields[] = $this->db->quoteIdentifier($field);
            $valueFields[] = ":{$field}";
        }
        return "INSERT INTO " . $this->db->quoteIdentifier($model->getTable()) . 
            " (" . implode(", ", $quotedFields) . ") VALUES (" . implode(', ', $valueFields) . ")";
    }
    
    public function select($parameters)
    {
        return sprintf(
            "SELECT %s FROM %s%s", 
            $parameters->getFields(), $parameters->getTable(), $parameters->getWhereClause()
        );
    }
}
