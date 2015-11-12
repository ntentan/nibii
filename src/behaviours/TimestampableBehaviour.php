<?php
namespace ntentan\nibii\behaviours;

class TimestampableBehaviour extends \ntentan\nibii\Behaviour
{
    public function preSaveCallback($data)
    {
        $data['created'] = date('Y-m-d H:i:s');
        return $data;
    }
    
    public function preUpdateCallback($data)
    {
        $data['updated'] = date('Y-m-d H:i:s');
        return $data;
    }
}
