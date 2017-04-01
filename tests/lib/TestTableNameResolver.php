<?php

namespace ntentan\nibii\tests\lib;
use ntentan\nibii\interfaces\TableNameResolverInterface;

class TestTableNameResolver implements TableNameResolverInterface {
    
    public function getTableName($instance) {
        return ["schema" => "test_schema", "table" => "test_table"];
    }

}

