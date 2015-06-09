<?php
namespace ntentan\nibii;

class RecordWrapper
{
    protected $table;
    protected $datastore;
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
    
    protected function getDataStore()
    {
        $this->datastore = Nibii::getDefaultDatastoreInstance();
        return $this->datastore;
    }
    
    public function getDescription()
    {
        if($this->description === null)
        {
            $this->description = $this->getDataStore()->describe($this->table);
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
        return $this->getDataStore()->save($this);
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
