<?php

namespace ntentan\nibii;

class Operations
{

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
        'delete', 'count', 'update', 'with'
    ];
    private $dataOperationMethods = [
        'save', 'validate'
    ];

    public function __construct(RecordWrapper $wrapper, $table)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $driver = ORMContext::getInstance()->getDbContext()->getDriver();
        $this->dataOperations = new DataOperations($wrapper, $driver);
        $this->queryOperations = new QueryOperations($wrapper, $this->dataOperations, $driver);
    }

    public function perform($name, $arguments)
    {
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

    public function getData()
    {
        return $this->dataOperations->getData();
    }

    public function getInvalidFields()
    {
        return $this->dataOperations->getInvalidFields();
    }

}
