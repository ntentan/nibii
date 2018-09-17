<?php

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\kaikai\Cache;
use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\nibii\interfaces\DriverAdapterFactoryInterface;
use ntentan\nibii\interfaces\ValidatorFactoryInterface;

/**
 * A global class with information and utilities required by the rest of the ORM system.
 */
class ORMContext
{

    private static $instance;
    private $cache;
    private $modelFactory;
    private $modelValidatorFactory;
    private $driverAdapterFactory;

    private function __construct(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache)
    {
        $this->modelFactory = $modelFactory;
        $this->cache = $cache;
        $this->driverAdapterFactory = $driverAdapterFactory;
        $this->modelValidatorFactory = $modelValidatorFactory;
    }

    public static function initialize(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache): ORMContext
    {
        self::$instance = new self($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
        return self::$instance;
    }

    /**
     * A helper for loading a method described as a string.
     * @param string $path Model name as string 
     * @return \nibii\RecordWrapper
     * @throws NibiiException
     */
    public function load($path)
    {
        try {
            return $this->modelFactory->createModel($path, null);
        } catch (\ntentan\panie\exceptions\ResolutionException $e) {
            throw new
            NibiiException("Failed to load model [$path]. The class [$className] could not be found.");
        }
    }

    /**
     * @param RecordWrapper $instance
     */
    public function getModelTable($instance)
    {
        return $this->modelFactory->getModelTable($instance);
    }

    public function getDriverAdapter()
    {
        return $this->driverAdapterFactory->createDriverAdapter();
    }

    /**
     * @param string $class
     */
    public function getModelName($class)
    {
        return $class;
    }

    public static function getInstance() : ORMContext
    {
        if (self::$instance === null) {
            throw new NibiiException("A context has not yet been initialized");
        }
        return self::$instance;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getModelDescription($model) : ModelDescription
    {
        return new ModelDescription($model);
    }
    
    public function getModelValidatorFactory() : ValidatorFactoryInterface
    {
        return $this->modelValidatorFactory;
    }
    
    public function getModelFactory() : ModelFactoryInterface
    {
        return $this->modelFactory;
    }

    /**
     * 
     * @return \ntentan\atiaa\DbContext
     */
    public function getDbContext()
    {
        return DbContext::getInstance();
    }

    public function __destruct()
    {
        self::$instance = null;
    }

}
