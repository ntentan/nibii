<?php
/**
 * Created by PhpStorm.
 * User: ekow
 * Date: 9/6/17
 * Time: 8:58 AM
 */

namespace ntentan\nibii;

use ntentan\utils\Text;


class DriverAdapterFactory implements DriverAdapterFactoryInterface
{
    private $driverName;

    public function __construct($config)
    {
        $this->driverName = $config['driver'];
    }

    public function createDriverAdapter()
    {
        $class = __NAMESPACE__ . '\adapters\\' . Text::ucamelize($this->driverName) . 'Adapter';
        return new $class();
    }
}
