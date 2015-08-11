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

namespace ntentan\nibii\relationships;

use ntentan\nibii\QueryParameters;
use ntentan\utils\Text;

class BelongsToRelationship extends \ntentan\nibii\Relationship
{
    public function getQuery($data)
    {
        $query = (new QueryParameters($this->getModelInstance()))
            ->addFilter($this->foreignKey, [$data[$this->localKey]])
            ->setFirstOnly(true);
        return $query;
    }

    public function setup($table, $primaryKey)
    {
        $model = $this->getModelInstance();
        if($this->foreignKey == null) {
            $this->foreignKey = $model->getDescription()->getPrimaryKey()[0];
        }
        if($this->localKey == null) {
            $this->localKey = Text::singularize($model->getTable()) . '_id';
        }
    }
}
