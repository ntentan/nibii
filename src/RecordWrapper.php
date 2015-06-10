<?php
namespace ntentan\nibii;

class RecordWrapper
{
    protected $table;
    protected $adapter;
    private $description;
    private $data;

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
    
    public function getDescription()
    {
        if($this->description === null)
        {
            $this->description = $this->getDataAdapter()->describe($this->table);
        }
        return $this->description;
    }
    
    public static function getNew()
    {
        $class = get_called_class();
        return new $class();
    }
    
    public function save()
    {
        return $this->getDataAdapter()->getQueryEngine()->insert($this);
    }
    
    public static function getAll()
    {
        return $this->getDataAdapter()->getQueryEngine()->fetch();
    }
    
    public function __call($name, $arguments) 
    {
        
    }
    
    public function __callStatic($name, $arguments) 
    {
        $class = get_called_class();
        $instance = new $class();
        return $instance->__call($name, $arguments);
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
    
    public static function fetch()
    {
        $class = get_called_class();
        $instance = new $class();
        return $instance->getDataAdapter()->getQueryEngine()->fetch();
    }
}
