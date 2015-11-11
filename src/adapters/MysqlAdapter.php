<?php
namespace ntentan\nibii\adapters;

class MysqlAdapter extends \ntentan\nibii\DriverAdapter
{
    public function mapDataTypes($nativeType)
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
            case 'datetime':
                return 'datetime';
            case 'text':
                return 'text';
            case 'date':
                return 'date';
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
