<?php

namespace ntentan\nibii;

use ntentan\utils\validator\Validation;

class UniqueValidation extends Validation
{
    /**
     * @var RecordWrapper
     */
    private $model;
    private $mode;

    public function __construct($params)
    {
        $this->model = $params['model'];
        $this->mode = $params['mode'];
    }

    public function run($field, $data)
    {
        $test = [];
        foreach ($field['name'] as $name) {
            $test[$name] = isset($data[$name]) ? $data[$name] : null;
        }

        if ($test[$name] === null) {
            return true;
        }

        if ($this->mode == DataOperations::MODE_UPDATE) {
            $primaryKeyFields = $this->model->getDescription()->getPrimaryKey();
            $keys = [];
            foreach ($primaryKeyFields as $name) {
                $keys[$name] = $data[$name];
            }
            $testItem = $this->model->createNew()->fetchFirst($test);
            $intersection = array_intersect($testItem->toArray(), $keys);
            if (!empty($intersection)) {
                return true;
            }
        } else {
            $testItem = ((new \ReflectionClass($this->model))->newInstance())->fields(array_keys($test))->fetchFirst($test);
        }

        return $this->evaluateResult(
            $field, $testItem->count() === 0, 'The value of '.implode(', ', $field['name']).' must be unique'
        );
    }
}
