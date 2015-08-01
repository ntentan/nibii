<?php

namespace ntentan\nibii;

/**
 * Description of QueryParameters
 *
 * @author ekow
 */
class QueryParameters 
{
    private $whereClause;
    private $and = '';
    private $boundData = [];
    private $fields = [];
    private $table;
    private $db;
    private $firstOnly = false;
    
    public function __construct($driver, $table)
    {
        $this->db = $driver;
        $this->table = $table;
    }
    
    public function getFields()
    {
        $fields = '*';
        
        if(count($this->fields) > 0)
        {
            $fields = implode(', ', $this->fields);
        }
        
        return $fields;
    }
    
    public function setFields($fields)
    {
        $this->fields = $fields;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getWhereClause()
    {
        return $this->whereClause ? " WHERE {$this->whereClause}" : '';
    }
    
    public function getBoundData()
    {
        return $this->boundData;
    }
    
    public function addFilter($field, $values = [])
    {
        $this->whereClause .= $this->and;
        $numValues = count($values);
        if($numValues === 1)
        {
            $this->whereClause .= "{$field} = ?";
            $this->boundData[] = reset($values);
        }
        else
        {
            $this->whereClause .= "{$field} IN (?" . str_repeat(', ?', $numValues - 1) . ')';
            $this->boundData += $values;
        }
        $this->and = ' AND ';
    }
    
    public function setRawFilter($filter, $values)
    {
        $this->whereClause .= "{$this->and}$filter" ;
        $this->boundData += $values;
    }
    
    public function setFirstOnly($firstOnly)
    {
        $this->firstOnly = $firstOnly;
    }
    
    public function getFirstOnly()
    {
        return $this->firstOnly;
    }
}
