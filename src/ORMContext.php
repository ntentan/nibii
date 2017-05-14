<?php

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\kaikai\Cache;
use ntentan\panie\Container;

/**
 * A collection of utility methods used as helpers for loading
 * models.
 */
class ORMContext {
    
    private $container;
    private $dbContext;
    private static $instance;
    private $cache;
    
    public function __construct(Container $container) {
        $this->container = $container;
        $this->dbContext = $container->resolve(DbContext::class);
        $this->container->bind(interfaces\ModelJoinerInterface::class)
                ->to(Resolver::class);
        $this->container->bind(interfaces\TableNameResolverInterface::class)
                ->to(Resolver::class);
        $this->container->bind(interfaces\ModelClassResolverInterface::class)
                ->to(Resolver::class);
        $this->cache = $this->container->resolve(Cache::class);
        self::$instance = $this;
    }

    /**
     * A helper for loading a method described as a string.
     * @param string $path Model name as string 
     * @return \nibii\RecordWrapper
     * @throws NibiiException
     */
    public function load($path) {
        try {
            $className = $this->getClassName($path);
            return $this->container->resolve($className);
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
    public function joinModels($classA, $classB) {
        return$this->container->singleton(interfaces\ModelJoinerInterface::class)
            ->getJunctionClassName($classA, $classB);
    }

    /**
     * @param RecordWrapper $instance
     */
    public function getModelTable($instance) {
        return$this->container->singleton(interfaces\TableNameResolverInterface::class)
            ->getTableName($instance);
    }

    public function getClassName($model, $context = null) {
        return $this->container->singleton(interfaces\ModelClassResolverInterface::class)
            ->getModelClassName($model, $context);
    }

    /**
     * @param string $class
     */
    public function getModelName($class) {
        return $class;
    }
    
    public static function getInstance() {
        if(self::$instance === null) throw new NibiiException("A context has not yet been initialized");
        return self::$instance;
    }
    
    public function getContainer() {
        return $this->container;
    }
    
    public function getCache() {
        return $this->cache;
    }
    
    /**
     * 
     * @return \ntentan\atiaa\DbContext
     */
    public function getDbContext() {
        return $this->dbContext;
    }
    
    public function __destruct() {
        self::$instance = null;
    }

}
