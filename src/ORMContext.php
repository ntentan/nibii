<?php

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\nibii\interfaces\DriverAdapterFactoryInterface;
use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\nibii\interfaces\ValidatorFactoryInterface;
use ntentan\nibii\exceptions\NibiiException;
use ntentan\panie\exceptions\ResolutionException;
use ntentan\kaikai\Cache;

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
        $this->driverAdapterFactory = $driverAdapterFactory;
        $this->modelValidatorFactory = $modelValidatorFactory;
        $this->cache = $cache;
    }

    public static function initialize(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache)
    {
        self::$instance = new self($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
        return self::$instance;
    }

    /**
     * A helper for loading a method described as a string.
     */
    public function load($path)
    {
        try {
            return $this->modelFactory->createModel($path, "");
        } catch (ResolutionException $e) {
            throw new NibiiException("Failed to load model [$path]. {$e->getMessage()}");
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
     * @return string
     */
    public function getModelName($class)
    {
        return $class;
    }

    public static function getInstance() : self
    {
        if (self::$instance === null) {
            throw new NibiiException('A context has not yet been initialized. Maybe you need to supply a configuration?');
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
     * @return \ntentan\atiaa\DbContext
     * @throws \Exception
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
