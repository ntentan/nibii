<?php

namespace ntentan\nibii;

use ntentan\panie\Container;

class Operations {

    private $wrapper;

    /**
     *
     * @var \ntentan\nibii\DriverAdapter
     */
    private $adapter;
    private $queryOperations;
    private $dataOperations;
    private $queryOperationMethods = [
        'fetch', 'fetchFirst', 'filter', 'query', 'fields',
        'cover', 'limit', 'offset', 'filterBy', 'sortBy',
        'delete', 'count', 'update'
    ];
    private $dataOperationMethods = [
        'save'
    ];

    public function __construct(Container $container, RecordWrapper $wrapper, DriverAdapter $adapter, $table) {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
        $this->dataOperations = $container->resolve(DataOperations::class, ['adapter' => $adapter]);
        $this->queryOperations = $container->resolve(QueryOperations::class, ['dataOperations' => $this->dataOperations, 'adapter' => $adapter]);
    }

    public function perform($name, $arguments) {
        //@todo Think of using a hash here in future
        if (array_search($name, $this->queryOperationMethods) !== false) {
            return call_user_func_array([$this->queryOperations, "do$name"], $arguments);
        } else if (array_search($name, $this->dataOperationMethods) !== false) {
            return call_user_func_array([$this->dataOperations, "do$name"], $arguments);
        } else if ($this->queryOperations->initDynamicMethod($name)) {
            return $this->queryOperations->runDynamicMethod($arguments);
        } else {
            throw new NibiiException("Method {$name} not found");
        }
    }

    public function getData() {
        return $this->dataOperations->getData();
    }

    public function getInvalidFields() {
        return $this->dataOperations->getInvalidFields();
    }

}
