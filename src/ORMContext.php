<?php

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\nibii\interfaces\DriverAdapterFactoryInterface;
use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\nibii\interfaces\ValidatorFactoryInterface;
use ntentan\nibii\exceptions\NibiiException;
use ntentan\nibii\relationships\RelationshipType;
use ntentan\panie\exceptions\ResolutionException;
use ntentan\kaikai\Cache;

/**
 * A global class with information and utilities required by the rest of the ORM system.
 */
class ORMContext
{
    private static ORMContext $instance;
    private Cache $cache;
    private ModelFactoryInterface $modelFactory;
    private ValidatorFactoryInterface $modelValidatorFactory;
    private DriverAdapterFactoryInterface $driverAdapterFactory;

    private function __construct(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache)
    {
        $this->modelFactory = $modelFactory;
        $this->driverAdapterFactory = $driverAdapterFactory;
        $this->modelValidatorFactory = $modelValidatorFactory;
        $this->cache = $cache;
    }

    public static function initialize(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache): ORMContext
    {
        self::$instance = new self($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
        return self::$instance;
    }

    /**
     * A helper for loading a method described as a string.
     */
    public function load($path): RecordWrapper
    {
        try {
            return $this->modelFactory->createModel($path, RelationshipType::SELF);
        } catch (ResolutionException $e) {
            throw new NibiiException("Failed to load model [$path]. {$e->getMessage()}");
        }
    }

    public function getModelTable(RecordWrapper $instance): string
    {
        return $this->modelFactory->getModelTable($instance);
    }

    public function getDriverAdapter(): DriverAdapter
    {
        return $this->driverAdapterFactory->createDriverAdapter();
    }

    public function getModelName(string $class): string
    {
        return $class;
    }

    public static function getInstance() : self
    {
//        if (self::$instance === null) {
//            throw new NibiiException('A context has not yet been initialized. Maybe you need to supply a configuration?');
//        }

        return self::$instance;
    }

    public function getCache(): Cache
    {
        return $this->cache;
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

    public function getDbContext(): DbContext
    {
        return DbContext::getInstance();
    }
}
