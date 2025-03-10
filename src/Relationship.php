<?php

namespace ntentan\nibii;

use ntentan\nibii\relationships\RelationshipType;

/**
 * Relationships provide queries for fetching data from related models when using lazy loading.
 */
abstract class Relationship
{
    protected array $options = [];
    protected RelationshipType $type;
    protected string $setupName;
    protected string $setupTable;
    protected ?string $setupSchema;
    protected array $setupPrimaryKey;

    private bool $setup = false;
    private ?QueryParameters $query = null;
    protected bool $queryPrepared = false;
    protected array $invalidFields = [];

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function createQuery(): QueryParameters
    {
        if (!$this->query) {
            $this->query = new QueryParameters();
        }
        return $this->query;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function initialize(): void
    {
        if (!$this->setup) {
            $this->runSetup();
            $this->setup = true;
        }
    }

    /**
     * Gets an instance of the related model accessed through this class.
     *
     * @return RecordWrapper
     * @throws exceptions\NibiiException
     */
    public function getModelInstance(): RecordWrapper
    {
        $this->initialize();
        return ORMContext::getInstance()->getModelFactory()->createModel($this->options['model'], $this->type);
    }

    public function setup($name, $schema, $table, $primaryKey)
    {
        $this->setupName = $name;
        $this->setupTable = $table;
        $this->setupPrimaryKey = $primaryKey;
        $this->setupSchema = $schema;
    }

    public function getInvalidFields()
    {
        return $this->invalidFields;
    }
    
    public function prepareQuery($data)
    {
        $this->initialize();
        return $this->doPrepareQuery($data);
    }

    abstract public function preSave(&$wrapper, $value);

    abstract public function postSave(&$wrapper);

    abstract protected function doPrepareQuery($data);

    /**
     * @todo Cleanup this method. 
     * There should be a get Parameters method instead which returns the values that are passed to setup. Initialize
     * should be the main wrapper arround this.
     */
    abstract public function runSetup();
}
