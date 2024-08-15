<?php
namespace ntentan\nibii;

use ntentan\nibii\exceptions\NibiiException;

class Operations
{
    private RecordWrapper $wrapper;

    private DriverAdapter $adapter;

    private QueryOperations $queryOperations;

    private DataOperations $dataOperations;

    private array $queryOperationMethods = [
        'fetch', 'fetchFirst', 'filter', 'query', 'fields',
        'cover', 'limit', 'offset', 'filterBy', 'sortBy',
        'delete', 'count', 'update', 'with', 'fix'
    ];
    private array $dataOperationMethods = [
        'add', 'update', 'validate', 'save'
    ];

    public function __construct(RecordWrapper $wrapper) //, $table)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $driver = ORMContext::getInstance()->getDbContext()->getDriver();
        $this->dataOperations = new DataOperations($wrapper, $driver);
        $this->queryOperations = new QueryOperations($wrapper, $this->dataOperations, $driver);
    }

    public function perform(string $name, array $arguments): mixed
    {
        return match (true) {
            in_array($name, $this->queryOperationMethods) => 
                call_user_func_array([$this->queryOperations, "do$name"], $arguments),
            in_array($name, $this->dataOperationMethods) => 
                call_user_func_array([$this->dataOperations, "do$name"], $arguments),
            $this->queryOperations->initDynamicMethod($name) => 
                $this->queryOperations->runDynamicMethod($arguments),
            default => 
                throw new NibiiException("Method $name not found")
        };
    }

    public function getData(): mixed
    {
        return $this->dataOperations->getData();
    }

    public function getInvalidFields(): array
    {
        return $this->dataOperations->getInvalidFields();
    }
}
