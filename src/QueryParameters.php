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
    private $fields = [];
    private $table;
    private $db;
    private $firstOnly = false;
    private $eagerLoad = [];
    private $limit;
    private $offset;
    private $sorts = [];

    /**
     *
     * @param \ $model
     */
    public function __construct(DriverAdapter $db, $table) {
        $this->db = $db;
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

    public function getLimit() {
        return $this->limit > 0 ? " LIMIT {$this->limit}" : null;
    }

    public function getOffset() {
        return $this->offset > 0 ? " OFFSET {$this->offset}" : null;
    }

    public function getWhereClause() {
        return $this->whereClause ? " WHERE {$this->whereClause}" : '';
    }

    public function getBoundData() {
        return $this->boundData;
    }

    public function getSorts() {
        return count($this->sorts) ? " ORDER BY " . implode(", ", $this->sorts) : null;
    }

    public function addFilter($field, $values = []) {
        $this->whereClause .= $this->and;
        $numValues = count($values);
        $startIndex = count($this->boundData);

        if ($numValues === 1) {
            $key = "filter_{$startIndex}";
            if ($values[0] === null) {
                $this->whereClause .= "{$field} is NULL";
            } else {
                $this->whereClause .= "{$field} = :$key";
                $this->boundData[$key] = reset($values);
            }
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

    public function setRawFilter($filter, $values) {
        $this->whereClause .= "{$this->and}$filter";
        $this->boundData += $values;
    }

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

    public function addSort($field, $direction = 'ASC') {
        $this->sorts[] = "$field $direction";
    }

}
