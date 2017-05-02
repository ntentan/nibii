<?php

namespace ntentan\nibii;

use ntentan\panie\Container;
use ntentan\kaikai\Cache;
use ntentan\atiaa\DbContext;

/**
 * A collection of utility methods used as helpers for loading
 * models.
 */
class Context {
    
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
            return$this->container->resolve(self::getClassName($path));
        } catch (\ntentan\panie\exceptions\ResolutionException $e) {
            throw new
            NibiiException("Failed to load model [$path]. Please specify a valid database driver.");
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

    public function getModelTable($instance) {
        return$this->container->singleton(interfaces\TableNameResolverInterface::class)
            ->getTableName($instance);
    }

    public function getClassName($model, $context = null) {
        return$this->container->singleton(interfaces\ModelClassResolverInterface::class)
            ->getModelClassName($model, $context);
    }

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
    
    public function getDbContext() {
        return $this->dbContext;
    }
    
    public function __destruct() {
        self::$instance = null;
    }

}
