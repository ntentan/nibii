<?php
namespace ntentan\nibii\adapters;

class PostgresqlAdapter extends \ntentan\nibii\DriverAdapter
{
    protected function mapDataTypes($nativeType) 
    {
        switch($nativeType)
        {
            case 'character varying':
                return 'string';
            case 'integer':
            case 'boolean':
                return $nativeType;
            case 'timestamp without time zone':
                return 'datetime';
            /*case 'int':
                return 'integer';
            case 'varchar':
                return 'string';
            case 'tinyint':
                return 'boolean';
            case 'timestamp':
                return 'datetime';*/
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
