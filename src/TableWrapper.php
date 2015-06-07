<?php
namespace ntentan\nibii;

class TableWrapper
{
    protected $table;
    protected $datastore;
    private $description;

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
    
    protected function getDescription()
    {
        if($this->description === null)
        {
            $this->description = $this->getDataStore()->describe($this->table);
        }
        return $this->description;
    }
    
    public function getFields()
    {
        return $this->getDescription()['fields'];
    }
}
