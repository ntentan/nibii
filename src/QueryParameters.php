<?php

namespace ntentan\nibii;

/**
 * Description of QueryParameters
 *
 * @author ekow
 */
class QueryParameters {

    private $whereClause;
    private $and = '';
    private $boundData = [];
    private $preparedBoundData = false;
    private $boundArrays = [];
    private $fields = [];
    private $table;
    private $firstOnly = false;
    private $eagerLoad = [];
    private $limit;
    private $offset;
    private $sorts = [];

    /**
     *
     * @param string $table The name of the table
     */
    public function __construct($table = null) {
        $this->table = $table;
    }

    public function getFields() {
        $fields = '*';

        if (count($this->fields) > 0) {
            $fields = implode(', ', $this->fields);
        }

        return $fields;
    }

    public function getEagerLoad() {
        return $this->eagerLoad;
    }

    public function setFields($fields) {
        $this->fields = $fields;
        return $this;
    }

    public function getTable() {
        return $this->table;
    }
    
    public function setTable($table) {
        $this->table = $table;
        return $this;
    }

    public function getLimit() {
        return $this->limit > 0 ? " LIMIT {$this->limit}" : null;
    }

    public function getOffset() {
        return $this->offset > 0 ? " OFFSET {$this->offset}" : null;
    }

    public function getWhereClause() {
        if($this->whereClause) {
            foreach($this->boundArrays as $boundArray) {
                $where = "";
                $comma = "";
                for($i = 0; $i < count($this->boundData[$boundArray]); $i++) {
                    $where .= "{$comma}:{$boundArray}_{$i}";
                    $comma = ', ';
                }
                $this->whereClause = str_replace("%{$boundArray}%", $where, $this->whereClause);
            }
        }
        return $this->whereClause ? " WHERE {$this->whereClause}" : '';
    }

    public function getBoundData() {
        if($this->preparedBoundData === false) {
            $this->preparedBoundData = [];
            foreach($this->boundData as $key => $value) {
                if(in_array($key, $this->boundArrays)) {
                    foreach($value as $i => $v) {
                        $this->preparedBoundData["{$key}_{$i}"] = $v;
                    }
                } else {
                    $this->preparedBoundData[$key] = $value;
                }
            }
        }        
        return $this->preparedBoundData;
    }

    public function setBoundData($key, $value) {
        if(isset($this->boundData[$key])){
            $isArray = is_array($value);
            $boundArray = in_array($key, $this->boundArrays);
            if($isArray && !$boundArray) {
                throw new NibiiException("{$key} cannot be bound to an array");
            } else if (!$isArray && $boundArray) {
                throw new NibiiException("{$key} must be bound to an array");
            }
            $this->boundData[$key] = $value;
            $this->preparedBoundData = false;
            return $this;
        }
        throw new NibiiException("{$key} has not been bound to the current query");
    }

    public function getSorts() {
        return count($this->sorts) ? " ORDER BY " . implode(", ", $this->sorts) : null;
    }

    public function addFilter($field, $values = null) {
        $this->whereClause .= $this->and;

        if (is_array($values)) {
            $this->whereClause .= "{$field} IN (%{$field}%)";
            $this->boundArrays[] = $field;
            $this->boundData[$field] = $values;
        } else {
            if ($values === null) {
                $this->whereClause .= "{$field} is NULL";
            } else {
                $this->whereClause .= "{$field} = :$field";
                $this->boundData[$field] = $values;
            }
        }
        $this->and = ' AND ';
        return $this;
    }

    public function setRawFilter($filter, $values) {
        $this->whereClause .= "{$this->and}$filter";
        $this->boundData += $values;
    }

    /**
     * @param boolean $firstOnly
     */
    public function setFirstOnly($firstOnly) {
        $this->firstOnly = $firstOnly;
        return $this;
    }

    public function getFirstOnly() {
        return $this->firstOnly;
    }

    public function setLimit($numItems) {
        $this->limit = $numItems;
    }

    public function setOffset($offset) {
        $this->offset = $offset;
    }

    /**
     * @param string $field
     */
    public function addSort($field, $direction = 'ASC') {
        $this->sorts[] = "$field $direction";
    }

}
