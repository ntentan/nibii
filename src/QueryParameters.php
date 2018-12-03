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

use ntentan\nibii\exceptions\FieldNotFoundException;
use ntentan\nibii\exceptions\NibiiException;

/**
 * Holds parameters used for the fields, where clauses, limits and offsets in queries.
 *
 * @author ekow
 */
class QueryParameters
{
    /**
     * The where clause string of the query parameters.
     *
     * @var string
     */
    private $whereClause;

    /**
     * The string used for conjuctions.
     * This could either be 'AND' and or an 'OR' operator.
     *
     * @var string
     */
    private $conjunction = '';

    /**
     * Data that will be bound when a query is executed with this object.
     *
     * @var array
     */
    private $boundData = [];

    /**
     * This flag is set to true whenever there is bound data prepared.
     *
     * @var bool
     */
    private $preparedBoundData = false;

    /**
     * A list of fields that have arrays bound to them.
     *
     * @var array
     */
    private $boundArrays = [];

    /**
     * A list of fields that should be returned for the query.
     *
     * @var array
     */
    private $fields = [];

    /**
     * The database table to be queried.
     *
     * @var null|string
     */
    private $table;

    /**
     * When this flag is set, only the first item in the query is returned.
     * It essentially forces a limit of 1.
     *
     * @var bool
     */
    private $firstOnly = false;

    /**
     * The number of records to return after the query.
     *
     * @var int
     */
    private $limit;

    /**
     * The number of items to skip in the query.
     *
     * @var int
     */
    private $offset;

    /**
     * Holds a list of sorted fields, sort order and the order by which they should all be sorted.
     *
     * @var array
     */
    private $sorts = [];

    /**
     * QueryParameters constructor.
     *
     * @param string $table The name of the table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
    }

    /**
     * Get the comma seperated list of fields for the query.
     * In cases where no fields have been specifid, the wildcard * is returned.
     *
     * @return string
     */
    public function getFields()
    {
        $fields = '*';

        if (count($this->fields) > 0) {
            $fields = implode(', ', $this->fields);
        }

        return $fields;
    }

    /**
     * Set an array of fields that this query should return.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the table for this query.
     *
     * @return null|string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the table for this query.
     *
     * @param $table
     *
     * @return $this For chaining
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Gets the limit clause of the query.
     *
     * @return null|string
     */
    public function getLimit()
    {
        return $this->limit > 0 ? " LIMIT {$this->limit}" : null;
    }

    public function getOffset()
    {
        return $this->offset > 0 ? " OFFSET {$this->offset}" : null;
    }

    public function getWhereClause()
    {
        if ($this->whereClause) {
            foreach ($this->boundArrays as $boundArray) {
                $where = '';
                $comma = '';
                for ($i = 0; $i < count($this->boundData[$boundArray]); $i++) {
                    $where .= "{$comma}:{$boundArray}_{$i}";
                    $comma = ', ';
                }
                $this->whereClause = str_replace("%{$boundArray}%", $where, $this->whereClause);
            }
        }

        return $this->whereClause ? " WHERE {$this->whereClause}" : '';
    }

    public function getBoundData()
    {
        if ($this->preparedBoundData === false) {
            $this->preparedBoundData = [];
            foreach ($this->boundData as $key => $value) {
                if (in_array($key, $this->boundArrays)) {
                    foreach ($value as $i => $v) {
                        $this->preparedBoundData["{$key}_{$i}"] = $v;
                    }
                } else {
                    $this->preparedBoundData[$key] = $value;
                }
            }
        }

        return $this->preparedBoundData;
    }

    public function setBoundData($field, $value)
    {
        if (array_key_exists($field, $this->boundData)) {
            $isArray = is_array($value);
            $boundArray = in_array($field, $this->boundArrays);
            if ($isArray && !$boundArray) {
                throw new NibiiException("The field '{$field}' cannot be bound to an array");
            } elseif (!$isArray && $boundArray) {
                throw new NibiiException("The field '{$field}' must be bound to an array");
            }
            $this->boundData[$field] = $value;
            $this->preparedBoundData = false;

            return $this;
        }

        throw new FieldNotFoundException("The field '{$field}' has not been bound to the current query");
    }

    public function getSorts()
    {
        return count($this->sorts) ? ' ORDER BY '.implode(', ', $this->sorts) : null;
    }

    public function addFilter($field, $values = null)
    {
        $this->whereClause .= $this->conjunction;

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
        $this->conjunction = ' AND ';

        return $this;
    }

    public function setFilter($filter, $values)
    {
        $filterCompiler = new FilterCompiler();
        $compiledFilter = $filterCompiler->compile($filter);
        $compiledValues = $filterCompiler->rewriteBoundData($values);
        $this->whereClause .= "{$this->conjunction}$compiledFilter";
        $this->boundData += $compiledValues;
    }

    /**
     * @param bool $firstOnly
     *
     * @return $this
     */
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

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @param string $field
     * @param string $direction
     */
    public function addSort($field, $direction = 'ASC')
    {
        $this->sorts[] = "$field $direction";
    }
}
