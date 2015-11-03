<?php
namespace ntentan\nibii;

class ModelValidator extends \ntentan\utils\Validator
{
    private $model;
    
    /**
     * 
     * @param RecordWrapper
     */
    public function __construct($model) {
        $pk = null;
        $rules = [];
        $this->model = $model;
        $description = $model->getDescription();

        if($description->getAutoPrimaryKey()) {
            $pk = $description->getPrimaryKey()[0];
        }

        $fields = $description->getFields();
        foreach($fields as $field) {
            $this->getFieldRules($rules, $field, $pk);
        }
        
        $unique = $description->getUniqueKeys();
        foreach($unique as $constraints) {
            $rules['unique'][] = [$constraints['fields']];
        }
        
        $this->setRules($rules);
    }

    private function getFieldRules(&$rules, $field, $pk)
    {
        if($field['required'] && $field['name'] != $pk && $field['default'] === null) {
            $rules['required'][] = $field['name'];
        }
        if($field['type'] === 'integer' || $field['type'] === 'double') {
            $rules['numeric'][] = $field['name'];
        }
    }
    
    protected function validateUnique($field, $data)
    {
        $test = [];
        foreach($field['name'] as $name) {
            $test[$name] = isset($data[$name]) ? $data[$name] : null;
        }
        return $this->evaluateResult(
            $field, 
            $this->model->createNew()->fetch($test)->count() === 0,
            $data
        );
    }
}
