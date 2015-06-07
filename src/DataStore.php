<?php
namespace ntentan\nibii;

abstract class DataStore
{
    protected $settings;
    
    abstract public function init();
    abstract public function describe($table);
    
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }
}
