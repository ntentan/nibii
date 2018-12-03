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

class QueryEngine
{
    private $db;

    public function setDriver($driver)
    {
        $this->db = $driver;
    }

    /**
     * Generates an SQL insert query string for the model based on the fields
     * currently stored in the model.
     *
     * @param RecordWrapper $model
     *
     * @return string
     */
    public function getInsertQuery($model)
    {
        $data = $model->getData();
        $table = $model->getDBStoreInformation()['quoted_table'];
        $fields = array_keys($data[0]);
        $quotedFields = [];
        $valueFields = [];

        foreach ($fields as $field) {
            $quotedFields[] = $this->db->quoteIdentifier($field);
            $valueFields[] = ":{$field}";
        }

        return 'INSERT INTO '.$table.
            ' ('.implode(', ', $quotedFields).') VALUES ('.implode(', ', $valueFields).')';
    }

    public function getBulkUpdateQuery($data, $parameters)
    {
        $updateData = [];
        foreach ($data as $field => $value) {
            $updateData[] = "{$this->db->quoteIdentifier($field)} = :$field";
        }

        return sprintf(
            'UPDATE %s SET %s %s',
            $parameters->getTable(),
            implode(', ', $updateData),
            $parameters->getWhereClause()
        );
    }

    /**
     * Generates an SQL update query string for the model based on the data
     * currently stored in the model.
     *
     * @param RecordWrapper $model
     *
     * @return string
     */
    public function getUpdateQuery($model)
    {
        $data = $model->getData();
        $fields = array_keys($data[0]);
        $valueFields = [];
        $conditions = [];
        $primaryKey = $model->getDescription()->getPrimaryKey();

        foreach ($fields as $field) {
            $quotedField = $this->db->quoteIdentifier($field);

            if (array_search($field, $primaryKey) !== false) {
                $conditions[] = "{$quotedField} = :{$field}";
            } else {
                $valueFields[] = "{$quotedField} = :{$field}";
            }
        }

        return 'UPDATE '.
            $model->getDBStoreInformation()['quoted_table'].
            ' SET '.implode(', ', $valueFields).
            ' WHERE '.implode(' AND ', $conditions);
    }

    public function getSelectQuery($parameters)
    {
        return sprintf(
            'SELECT %s FROM %s%s%s%s%s',
            $parameters->getFields(),
            $parameters->getTable(),
            $parameters->getWhereClause(),
            $parameters->getSorts(),
            $parameters->getLimit(),
            $parameters->getOffset()
        );
    }

    public function getCountQuery($parameters)
    {
        return sprintf(
            'SELECT count(*) as count FROM %s%s',
            $parameters->getTable(),
            $parameters->getWhereClause()
        );
    }

    public function getDeleteQuery($parameters)
    {
        return sprintf(
            'DELETE FROM %s%s',
            $parameters->getTable(),
            $parameters->getWhereClause()
        );
    }
}
