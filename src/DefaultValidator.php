<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ntentan\nibii;

use ntentan\utils\Validator;

class DefaultValidator extends Validator
{
    private $model;

    /**
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
