<?php
namespace ntentan\nibii;

class QueryEngine
{

    private $db;

    public function setDriver($driver)
    {
        $this->db = $driver;
    }
    
    private function filter($query)
    {
        return $query;
    }

    public function getInsertQuery($model)
    {
        $data = $model->getData();
        $fields = array_keys($data[0]);
        $quotedFields = [];
        $valueFields = [];

        foreach ($fields as $field) {
            $quotedFields[] = $this->db->quoteIdentifier($field);
            $valueFields[] = ":{$field}";
        }

        return $this->filter(
            "INSERT INTO " . $this->db->quoteIdentifier($model->getTable()) .
            " (" . implode(", ", $quotedFields) . ") VALUES (" . implode(', ', $valueFields) . ")"
        );
    }

    public function getBulkUpdateQuery($data, $parameters)
    {
        $updateData = [];
        foreach($data as $field => $value) {
            $updateData[] = "{$this->db->quoteIdentifier($field)} = :$field";
        }

        return $this->filter(sprintf(
            "UPDATE %s SET %s %s",
            $parameters->getTable(),
            implode(', ', $updateData),
            $parameters->getWhereClause()
        ));
    }

    public function getUpdateQuery($model)
    {
        $data = $model->getData();
        $fields = array_keys($data[0]);
        $valueFields = [];
        $conditions = [];
        $primaryKey = $model->getDescription()->getPrimaryKey();

        foreach ($fields as $field) {
            $quotedField = $this->db->quoteIdentifier($field);

            if(array_search($field, $primaryKey) !== false) {
                $conditions[] = "{$quotedField} = :{$field}";
            } else {
                $valueFields[] = "{$quotedField} = :{$field}";
            }
        }

        return $this->filter("UPDATE " .
            $this->db->quoteIdentifier($model->getTable()) .
            " SET " . implode(', ', $valueFields) .
            " WHERE " . implode(' AND ', $conditions)
        );
    }

    public function getSelectQuery($parameters)
    {
        return $this->filter(sprintf(
                "SELECT %s FROM %s%s%s%s",
                $parameters->getFields(),
                $parameters->getTable(),
                $parameters->getWhereClause(),
                $parameters->getSorts(),
                $parameters->getLimit(),
                $parameters->getOffset()
            )
        );
    }
    
    public function getCountQuery($parameters)
    {
        return $this->filter(sprintf(
                "SELECT count(*) as count FROM %s%s",
                $parameters->getTable(),
                $parameters->getWhereClause()
            )
        );
    }

    public function getDeleteQuery($parameters)
    {
        return $this->filter(sprintf(
                "DELETE FROM %s%s",
                $parameters->getTable(),
                $parameters->getWhereClause()
            )
        );
    }
}
