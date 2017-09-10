<?php

namespace ntentan\nibii;

use ntentan\nibii\RecordWrapper;
use ntentan\utils\Validator;

class DefaultValidator extends Validator
{

    private $model;

    /**
     * 
     * @param RecordWrapper
     */
    public function __construct(RecordWrapper $model, $mode)
    {
        $this->model = $model;   
        $this->registerValidation('unique', '\ntentan\nibii\UniqueValidation', ['model' => $model, 'mode' => $mode]);
    }
    
    public function extractRules()
    {
        $pk = null;
        $rules = [];
        $description = $this->model->getDescription();

        if ($description->getAutoPrimaryKey()) {
            $pk = $description->getPrimaryKey()[0];
        }

        $fields = $description->getFields();
        foreach ($fields as $field) {
            $this->getFieldRules($rules, $field, $pk);
        }

        $unique = $description->getUniqueKeys();
        foreach ($unique as $constraints) {
            $rules['unique'][] = [$constraints['fields']];
        }

        $this->setRules($rules);
    }

    private function getFieldRules(&$rules, $field, $pk)
    {
        if ($field['required'] && $field['name'] != $pk && $field['default'] === null) {
            $rules['required'][] = $field['name'];
        }
        if ($field['type'] === 'integer' || $field['type'] === 'double') {
            $rules['numeric'][] = $field['name'];
        }
    }

}
