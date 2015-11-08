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
use ntentan\nibii\Nibii;

class BelongsToRelationship extends \ntentan\nibii\Relationship
{
    protected $type = self::BELONGS_TO;

    public function getQuery($data)
    {
        $query = (new QueryParameters($this->getModelInstance()))
            ->addFilter($this->options['foreign_key'], [$data[$this->options['local_key']]])
            ->setFirstOnly(true);
        return $query;
    }

    public function runSetup()
    {
        $model = (new \ReflectionClass(Nibii::getClassName($this->options['model'], self::BELONGS_TO)))->newInstance();
        if($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = $model->getDescription()->getPrimaryKey()[0];
        }
        if($this->options['local_key'] == null) {
            $this->options['local_key'] = Text::singularize($model->getTable()) . '_id';
        }
    }
}
