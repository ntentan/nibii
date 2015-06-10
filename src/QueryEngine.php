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
        $fields = array_keys($data);
        $quotedFields = [];
        $rawValues = [];
        foreach($fields as $field)
        {
            $quotedFields[] = $this->db->quoteIdentifier($field);
            $rawValues[] = $data[$field];
        }
        $this->db->query(
            "INSERT INTO " . $this->db->quoteIdentifier($model->getTable()) . 
            " (" . implode(", ", $quotedFields) . ") VALUES (?" . str_repeat(", ?", count($fields) - 1) . ")", 
            $rawValues
        ); 
    }
}
