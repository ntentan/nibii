<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ntentan\nibii;

use ntentan\atiaa\DbContext;
use ntentan\kaikai\Cache;
use ntentan\nibii\interfaces\DriverAdapterFactoryInterface;
use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\nibii\interfaces\ValidatorFactoryInterface;
use ntentan\nibii\exceptions\NibiiException;
use ntentan\panie\exceptions\ResolutionException;

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

    public static function initialize(ModelFactoryInterface $modelFactory, DriverAdapterFactoryInterface $driverAdapterFactory, ValidatorFactoryInterface $modelValidatorFactory, Cache $cache): self
    {
        self::$instance = new self($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);

        return self::$instance;
    }

    /**
     * A helper for loading a method described as a string.
     *
     * @param string $path Model name as string
     *
     * @throws NibiiException
     *
     * @return \nibii\RecordWrapper
     */
    public function load($path)
    {
        try {
            return $this->modelFactory->createModel($path, null);
        } catch (ResolutionException $e) {
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
     * @return string
     */
    public function getModelName($class)
    {
        return $class;
    }

    public static function getInstance() : self
    {
        if (self::$instance === null) {
            throw new NibiiException('A context has not yet been initialized');
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
