<?php
namespace ntentan\nibii\adapters;

class MysqlAdapter extends \ntentan\nibii\DriverAdapter
{
    protected function mapDataTypes($nativeType) 
    {
        switch($nativeType)
        {
            case 'int':
                return 'integer';
            case 'varchar':
                return 'string';
            case 'tinyint':
                return 'boolean';
            case 'timestamp':
                return 'datetime';
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
