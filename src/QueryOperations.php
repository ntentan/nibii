<?php

namespace ntentan\nibii;

use ntentan\atiaa\Driver;
use ntentan\nibii\exceptions\ModelNotFoundException;
use ntentan\utils\Text;

/**
 * Performs operations that query the database.
 */
class QueryOperations
{
    /**
     * An instance of the record wrapper being used.
     * @var RecordWrapper
     */
    private $wrapper;

    /**
     * An instance of the driver adapter used in the database connection.
     * @var DriverAdapter
     */
    private $adapter;

    /**
     * An instance of query parameters used to perform the various queries.
     * @var QueryParameters
     */
    private $queryParameters;

    /**
     * The name of a method initialized through a dynamic method waiting to be executed.
     *
     * @var string
     */
    private $pendingMethod;

    /**
     * Regular expressions for matching dynamic methods.
     *
     * @var array
     */
    private $dynamicMethods = [
        '/(?<method>filterBy)(?<variable>[A-Z][A-Za-z]+){1}/',
        '/(?<method>sort)(?<direction>Asc|Desc)?(By)(?<variable>[A-Z][A-Za-z]+){1}/',
        '/(?<method>fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/',
    ];

    /**
     * An instance of the DataOperations class used for filtered deletes.
     *
     * @var DataOperations
     */
    private $dataOperations;

    /**
     * An instance of the Driver class used for establishing database connections.
     *
     * @var Driver
     */
    private $driver;

    private $defaultQueryParameters = null;

    /**
     * QueryOperations constructor.
     *
     * @param RecordWrapper $wrapper
     * @param DataOperations $dataOperations
     * @param Driver $driver
     *
     * @throws \ReflectionException
     * @throws \ntentan\atiaa\exceptions\ConnectionException
     * @throws exceptions\NibiiException
     * @internal param DriverAdapter $adapter
     */
    public function __construct(RecordWrapper $wrapper, DataOperations $dataOperations, Driver $driver)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $this->dataOperations = $dataOperations;
        $this->driver = $driver;
    }

    /**
     * Fetches items from the database.
     *
     * @param int|array|QueryParameters $query
     *
     * @return RecordWrapper
     * @throws \ReflectionException
     * @throws \ntentan\atiaa\exceptions\ConnectionException
     * @throws exceptions\NibiiException
     */
    public function doFetch($query = null)
    {
        $parameters = $this->buildFetchQueryParameters($query);
        $data = $this->adapter->select($parameters);
        if (empty($data)) {
            return;
        } else {
            $results = $this->wrapper->fromArray($data, true);
            $results->fix($parameters);
            $this->resetQueryParameters();
            return $results;
        }
    }

    public function doFix(QueryParameters $queryParameters)
    {
        $this->defaultQueryParameters = clone $queryParameters;
    }

    /**
     * The method takes multiple types of arguments and converts it to a QueryParametersObject.
     * When this method receives null, it returns a new instance of QueryParameters. When it receives an integer, it
     * returns a QueryParameters object that points the primary key to the integer. When it receives an associative
     * array, it builds a series of conditions with array key-value pairs.
     *
     * @param int|array|QueryParameters $arg
     * @param bool $instantiate
     *
     * @return QueryParameters
     * @throws \ReflectionException
     * @throws \ntentan\atiaa\exceptions\ConnectionException
     * @throws exceptions\NibiiException
     */
    private function buildFetchQueryParameters($arg, $instantiate = true)
    {
        if ($arg instanceof QueryParameters) {
            $this->queryParameters = $arg;
            return $arg;
        }

        $parameters = $this->getQueryParameters($instantiate);

        if (is_numeric($arg)) {
            $description = $this->wrapper->getDescription();
            $parameters->addFilter($description->getPrimaryKey()[0], $arg);
            $parameters->setFirstOnly(true);
        } elseif (is_array($arg)) {
            foreach ($arg as $field => $value) {
                $parameters->addFilter($field, $value);
            }
        }

        return $parameters;
    }

    /**
     * Creates a new instance of the QueryParameters if required or just returns an already instance.
     * @param bool $forceInstantiation
     * @return QueryParameters
     */
    private function getQueryParameters($forceInstantiation = true)
    {
        if ($this->queryParameters === null && $forceInstantiation) {
            $this->queryParameters = new QueryParameters($this->wrapper->getDBStoreInformation()['quoted_table']);
        }
        return $this->queryParameters;
    }

    /**
     * Clears up the query parameters.
     */
    private function resetQueryParameters()
    {
        $this->queryParameters = $this->defaultQueryParameters ? clone $this->defaultQueryParameters : null;
    }

    /**
     * Performs the fetch operation and returns just the first item.
     *
     * @param mixed $id
     *
     * @return RecordWrapper
     * @throws \ReflectionException
     * @throws \ntentan\atiaa\exceptions\ConnectionException
     * @throws exceptions\NibiiException
     */
    public function doFetchFirst($id = null)
    {
        $this->getQueryParameters()->setFirstOnly(true);

        return $this->doFetch($id);
    }

    /**
     * Set the fields that should be returned for each record.
     *
     * @return RecordWrapper
     */
    public function doFields()
    {
        $fields = [];
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                $fields = array_merge($fields, $argument);
            } else {
                $fields[] = $argument;
            }
        }
        $this->getQueryParameters()->setFields($fields);

        return $this->wrapper;
    }

    /**
     * Sort the query by a given field in a given directory.
     *
     * @param string $field
     * @param string $direction
     */
    public function doSortBy($field, $direction = 'ASC')
    {
        $this->getQueryParameters()->addSort($field, $direction);
    }

    /**
     * @param mixed $arguments
     *
     * @return array
     */
    private function getFilter($arguments)
    {
        if (count($arguments) == 2 && is_array($arguments[1])) {
            $filter = $arguments[0];
            $data = $arguments[1];
        } else {
            $filter = array_shift($arguments);
            $data = $arguments;
        }

        return ['filter' => $filter, 'data' => $data];
    }

    public function doFilter()
    {
        $arguments = func_get_args();
        if (count($arguments) == 1 && is_array($arguments[0])) {
            foreach ($arguments[0] as $field => $value) {
                $this->getQueryParameters()->addFilter($field, $value);
            }
        } else {
            $details = $this->getFilter($arguments);
            $this->getQueryParameters()->setFilter($details['filter'], $details['data']);
        }

        return $this->wrapper;
    }

    public function doFilterBy()
    {
        $arguments = func_get_args();
        $details = $this->getFilter($arguments);
        $this->getQueryParameters()->addFilter($details['filter'], $details['data']);

        return $this->wrapper;
    }

    public function doUpdate($data)
    {
        $this->driver->beginTransaction();
        $parameters = $this->getQueryParameters();
        $this->adapter->bulkUpdate($data, $parameters);
        $this->driver->commit();
        $this->resetQueryParameters();
    }

    public function doDelete($args = null)
    {
        $this->driver->beginTransaction();
        $parameters = $this->buildFetchQueryParameters($args);

        if ($parameters === null) {
            $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
            $parameters = $this->getQueryParameters();
            $data = $this->wrapper->getData();
            $keys = [];

            foreach ($data as $datum) {
                if ($this->dataOperations->isItemDeletable($primaryKey, $datum)) {
                    $keys[] = $datum[$primaryKey[0]];
                }
            }

            $parameters->addFilter($primaryKey[0], $keys);
            $this->adapter->delete($parameters);
        } else {
            $this->adapter->delete($parameters);
        }

        $this->driver->commit();
        $this->resetQueryParameters();
    }

    public function runDynamicMethod($arguments)
    {
        $arguments = count($arguments) > 1 ? $arguments : ($arguments[0] ?? null);
        switch ($this->pendingMethod['method']) {
            case 'filterBy':
                $this->getQueryParameters()->addFilter(Text::deCamelize($this->pendingMethod['variable']), $arguments);

                return $this->wrapper;
            case 'sort':
                $this->getQueryParameters()->addSort(Text::deCamelize($this->pendingMethod['variable']), $this->pendingMethod['direction']);

                return $this->wrapper;
            case 'fetch':
                $parameters = $this->getQueryParameters();
                $parameters->addFilter(Text::deCamelize($this->pendingMethod['variable']), $arguments);
                if ($this->pendingMethod['first'] === 'First') {
                    $parameters->setFirstOnly(true);
                }

                return $this->doFetch();
        }
    }

    public function initDynamicMethod($method)
    {
        $return = false;

        foreach ($this->dynamicMethods as $regexp) {
            if (preg_match($regexp, $method, $matches)) {
                $return = true;
                $this->pendingMethod = $matches;
                break;
            }
        }

        return $return;
    }

    public function doCount($query = null)
    {
        return $this->adapter->count($this->buildFetchQueryParameters($query));
    }

    public function doLimit($numItems)
    {
        $this->getQueryParameters()->setLimit($numItems);

        return $this->wrapper;
    }

    public function doOffset($offset)
    {
        $this->getQueryParameters()->setOffset($offset);

        return $this->wrapper;
    }

    public function doWith($model)
    {
        if (!isset($this->wrapper->getDescription()->getRelationships()[$model])) {
            throw new ModelNotFoundException("Could not find related model [$model]");
        }
        $relationship = $this->wrapper->getDescription()->getRelationships()[$model];

        return $relationship->createQuery();
    }
}
