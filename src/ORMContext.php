<?php

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\kaikai\Cache;
use ntentan\panie\Container;

/**
 * A collection of utility methods used as helpers for loading
 * models.
 */
class ORMContext
{

    private $dbContext;
    private static $instance;
    private $cache;
    private $modelFactory;

    private function __construct(ModelFactoryInterface $modelFactory, DbContext $dbContext, Cache $cache)
    {
        $this->modelFactory = $modelFactory;
        $this->dbContext = $dbContext;
        $this->cache = $cache;
    }

    public static function initialize(ModelFactoryInterface $modelFactory, DbContext $dbContext, Cache $cache) : ORMContext
    {
        self::$instance = new self($modelFactory, $dbContext, $cache);
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
            NibiiException("Failed to load model [$path]. The class [$className] could not be found. Ensure that you have properly setup class name resolutions.");
        }
    }

    /**
     * Returns a class name for junction models needed to perform joint queries.
     * @param string $classA
     * @param string $classB
     * @return string
     */
    public function joinModels($classA, $classB)
    {
        return$this->container->singleton(interfaces\ModelJoinerInterface::class)
                        ->getJunctionClassName($classA, $classB);
    }

    /**
     * @param RecordWrapper $instance
     */
    public function getModelTable($instance)
    {
        return$this->container->singleton(interfaces\TableNameResolverInterface::class)
                        ->getTableName($instance);
    }

    /*public function getClassName($model, $context = null)
    {
        return $this->container->singleton(interfaces\ModelClassResolverInterface::class)
                        ->getModelClassName($model, $context);
    }*/

    /**
     * @param string $class
     */
    public function getModelName($class)
    {
        return $class;
    }

    public static function getInstance()
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

    /**
     * 
     * @return \ntentan\atiaa\DbContext
     */
    public function getDbContext()
    {
        return $this->dbContext;
    }

    public function __destruct()
    {
        self::$instance = null;
    }

}
