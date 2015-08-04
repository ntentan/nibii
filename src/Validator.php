<?php
namespace ntentan\nibii;

class Validator extends \ntentan\utils\Validator
{
    public function __construct($description) {
        $pk = null;
        $rules = [];        
        
        if($description['auto_primary_key']) {
            $pk = $description['primary_key'][0];
        }
        
        foreach($description['fields'] as $name => $field) {

            $rules[$name] = $this->getFieldRules($field, $pk);
        }
        
        $this->setRules($rules);
    }
    
    private function getFieldRules($field, $pk)
    {
        $fieldRules = [];
        if($field['required'] && $field['name'] != $pk && $field['default'] === null) {
            $fieldRules[] = 'required';
        }
        if($field['type'] === 'integer' || $field['type'] === 'double') {
            $fieldRules[] = 'numeric';
        }      
        return $fieldRules;
    }    
}