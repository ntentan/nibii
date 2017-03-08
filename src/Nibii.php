<?php

namespace ntentan\nibii;

use ntentan\panie\InjectionContainer;

/**
 * A collection of utility static methods used as helpers for loading
 * models.
 */
class Nibii {

    /**
     * A helper for loading a method described as a string.
     * @param string $path Model name as string
     * @return \nibii\RecordWrapper
     * @throws NibiiException
     */
    public static function load($path) {
        try {
            return InjectionContainer::resolve(self::getClassName($path));
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
    public static function joinModels($classA, $classB) {
        return InjectionContainer::singleton(interfaces\ModelJoinerInterface::class)
            ->getJunctionClassName($classA, $classB);
    }

    public static function getModelTable($instance) {
        return InjectionContainer::singleton(interfaces\TableNameResolverInterface::class)
            ->getTableName($instance);
    }

    public static function getClassName($model, $context = null) {
        return InjectionContainer::singleton(interfaces\ModelClassResolverInterface::class)
            ->getModelClassName($model, $context);
    }

    public static function getModelName($class) {
        return $class;
    }

    public static function setupDefaultBindings() {
        InjectionContainer::bind(interfaces\ModelJoinerInterface::class)
            ->to(Resolver::class);
        InjectionContainer::bind(interfaces\TableNameResolverInterface::class)
            ->to(Resolver::class);
        InjectionContainer::bind(interfaces\ModelClassResolverInterface::class)
            ->to(Resolver::class);
    }

}
