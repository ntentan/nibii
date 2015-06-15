<?php
namespace ntentan\nibii;

use ntentan\utils\Text;

class RecordWrapper implements \ArrayAccess, \Countable
{
    protected $table;
    protected $adapter;
    private $description;
    private $data = [];
    private $queryParameters;

    public function __construct()
    {
        if($this->table === null)
        {
            $this->table = $this->getDefaultTable();
        }
    }
    
    protected function getDefaultTable()
    {
        $class = new \ReflectionClass($this);
        $nameParts = explode("\\", $class->getName());
        return \ntentan\utils\Text::deCamelize(end($nameParts));
    }
    
    protected function getDataAdapter()
    {
        $this->adapter = DriverAdapter::getDefaultInstance();
        return $this->adapter;
    }
    
    /**
     * 
     * @return \ntentan\nibii\QueryParameters
     */
    protected function getQueryParameters()
    {
        if($this->queryParameters == null)
        {
            $this->queryParameters = new QueryParameters($this->getDataAdapter()->getDriver(), $this->table);
        }
        return $this->queryParameters;
    }
    
    public function getDescription()
    {
        if($this->description === null)
        {
            $this->description = $this->getDataAdapter()->describe($this->table);
        }
        return $this->description;
    }
    
    public static function createNew()
    {
        $class = get_called_class();
        return new $class();
    }
    
    public function save()
    {
        return $this->getDataAdapter()->insert($this);
    }
    
    private static function getInstance()
    {
        $class = get_called_class();
        return new $class();
    }
    
    private function doFetch($id = null)
    {
        $instance = isset($this) ? $this : self::getInstance();
        $adapter = $instance->getDataAdapter();
        $parameters = $instance->getQueryParameters();
        if($id !== null)
        {
            $description = $instance->getDescription();
            $parameters->addFilter($description['primary_key'][0], [$id]);
            $parameters->setFirstOnly(true);
        }
        $instance->data = $adapter->select($parameters);
        return $instance;
    }
    
    private function doFetchFirst()
    {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch();        
    }
    
    private function doFilter()
    {
        $arguments = func_get_args();
        $this->getQueryParameters()->setRawFilter(FilterCompiler::compile(array_shift($arguments)), $arguments);
        return $this;        
    }
    
    private function doFields()
    {
        $arguments = func_get_args();
        $this->getQueryParameters()->setFields($arguments);
        return $this;     
    }
        
    public function __call($name, $arguments) 
    {
        if(array_search($name, ['fetch', 'fetchFirst', 'filter', 'fields']) !== false)
        {
            $method = "do{$name}";
            return call_user_func_array([$this, $method], $arguments);
        }
        else if(preg_match("/(filterBy)(?<variable>[A-Za-z]+)/", $name, $matches))
        {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
            return $this;
        }
        else if(preg_match("/(fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/", $name, $matches))
        {
            $parameters = $this->getQueryParameters();
            $parameters->addFilter(Text::deCamelize($matches['variable']), $arguments);
            if($matches['first'] === 'First')
            {
                $parameters->setFirstOnly(true);
            }
            return $this->doFetch();
        }
        else
        {
            return call_user_func_array([$this->getDataAdapter(), $name], $arguments);
        }
    }
    
    public static function __callStatic($name, $arguments) 
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }
    
    public function __set($name, $value) 
    {
        $this->data[$name] = $value;
    }
    
    public function __get($name)
    {
        return $this->data[$name];
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }

    public function offsetExists($offset) 
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) 
    {
        if(is_numeric($offset))
        {
            return $this->wrap($offset);
        }
        else
        {
            return $this->data[$offset];
        }
    }

    public function offsetSet($offset, $value) 
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset) 
    {
        unset($this->data[$offset]);
    }

    public function count($mode = 'COUNT_NORMAL') 
    {
        if(@reset(array_keys($this->data)) === 0)
        {
            return count($this->data);
        }
        else
        {
            return 1;
        }
    }
    
    private function wrap($offset)
    {
        $newInstance = clone $this;
        $newInstance->setData($this->data[$offset]);
        return $newInstance;
    }
}
