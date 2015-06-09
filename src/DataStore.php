<?php
namespace ntentan\nibii;

abstract class DataStore
{
    protected $settings;
    protected $data;
    
    abstract public function init();
    abstract public function describe($table);
    abstract public function save($model);
    
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
}
