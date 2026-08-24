<?php
namespace ntentan\nibii\interfaces;

use ntentan\nibii\DriverAdapter;

interface DriverAdapterFactoryInterface
{
    public function createDriverAdapter(): DriverAdapter;
}
