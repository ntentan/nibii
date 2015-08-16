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
    private $eagerLoad = [];
    private $limit;

    /**
     *
     * @param \ $model
     */
    public function __construct($wrapper)
    {
        $this->db = DriverAdapter::getDefaultInstance();
        $this->table = $wrapper->getTable();
    }

    public function getFields()
    {
        $fields = '*';

        if (count($this->fields) > 0) {
            $fields = implode(', ', $this->fields);
        }

        return $fields;
    }

    public function getEagerLoad()
    {
        return $this->eagerLoad;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }
    
    public function getLimit()
    {
        return " LIMIT {$this->limit}";
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
        $startIndex = count($this->boundData);

        if ($numValues === 1) {
            $key = "filter_{$startIndex}";
            $this->whereClause .= "{$field} = :$key";
            $this->boundData[$key] = reset($values);
        } else {
            $this->whereClause .= "{$field} IN (";
            $comma = '';
            for ($i = 0; $i < $numValues; $i++) {
                $key = "filter_" . ($startIndex + $i);
                $this->whereClause .= "$comma:$key";
                $this->boundData[$key] = $values[$i];
                $comma = ' ,';
            }
            $this->whereClause .= ")";
        }
        $this->and = ' AND ';
        return $this;
    }

    public function setRawFilter($filter, $values)
    {
        $this->whereClause .= "{$this->and}$filter";
        $this->boundData += $values;
    }

    public function setFirstOnly($firstOnly)
    {
        $this->firstOnly = $firstOnly;
        return $this;
    }

    public function getFirstOnly()
    {
        return $this->firstOnly;
    }
    
    public function setLimit($numItems)
    {
        $this->limit = $numItems;
    }
}
