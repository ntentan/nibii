<?php
namespace ntentan\nibii\adapters;

class PostgresqlAdapter extends \ntentan\nibii\DriverAdapter
{
    /**
     * Convert from postgresqls native type to a generic type accepted in the
     * atiaa library.
     * @param string $nativeType
     * @return string
     * @throws \Exception
     */
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
