<?php

/*
 * The MIT License
 *
 * Copyright 2015 ekow.
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

use ntentan\utils\validator\Validation;

class UniqueValidation extends Validation
{

    /**
     *
     * @var \ntentan\nibii\RecordWrapper
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

        if ($this->mode == \ntentan\nibii\DataOperations::MODE_UPDATE) {
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
            $field, $testItem->count() === 0, "The value of " . implode(', ', $field['name']) . " must be unique"
        );
    }

}
