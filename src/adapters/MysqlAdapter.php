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
                return 'datetime';
            case 'text':
                return 'text';
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
