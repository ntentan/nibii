<?php
namespace ntentan\nibii;

use ntentan\utils\Text;

class RecordWrapper
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
        
    public function __call($name, $arguments) 
    {
        if($name === 'fetch')
        {
            return call_user_func_array([$this, 'doFetch'], $arguments);
        }
        else if(preg_match("/(filterBy)(?<variable>[A-Za-z]+)/", $name, $matches))
        {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
            return $this;
        }
        else if(preg_match("/(fetch)(First)?(With)(?<variable>[A-Za-z]+)/", $name, $matches))
        {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
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
}
