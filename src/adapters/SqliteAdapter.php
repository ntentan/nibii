<?php
namespace ntentan\nibii\adapters;

use ntentan\nibii\DriverAdapter;

class SqliteAdapter extends DriverAdapter
{
    public function mapDataTypes($nativeType)
    {
        switch ($nativeType) {
            case "INTEGER":
                return "integer";
                break;
            case "TEXT":
                return "string";
                break;
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
