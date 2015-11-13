<?php
namespace ntentan\nibii\behaviours;

class TimestampableBehaviour extends \ntentan\nibii\Behaviour
{
    /**
     *
     * @var \ntentan\nibii\RecordWrapper
     */
    protected $model;
    
    public function setModel($model)
    {
        $this->model = $model;
    }
    
    public function preSaveCallback($data)
    {
        if(isset($this->model->getDescription()->getFields()['created']))
        {
            $data['created'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
    
    public function preUpdateCallback($data)
    {
        if(isset($this->model->getDescription()->getFields()['updated']))
        {
            $data['updated'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
