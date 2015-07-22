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
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
