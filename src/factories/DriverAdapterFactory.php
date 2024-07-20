<?php
namespace ntentan\nibii\factories;

use ntentan\nibii\interfaces\DriverAdapterFactoryInterface;
use ntentan\utils\Text;
use ntentan\nibii\DriverAdapter;

class DriverAdapterFactory implements DriverAdapterFactoryInterface
{
    private $driverName;

    public function __construct(string $driverName)
    {
        $this->driverName = $driverName;
    }

    #[\Override]
    public function createDriverAdapter(): DriverAdapter
    {
        $class = 'ntentan\nibii\adapters\\'.Text::ucamelize($this->driverName).'Adapter';
        return new $class();
    }
}
