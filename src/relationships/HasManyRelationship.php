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

use ntentan\utils\Text;
use ntentan\nibii\ORMContext;

class HasManyRelationship extends \ntentan\nibii\Relationship
{
    protected $type = self::HAS_MANY;
    
    public function __construct(ORMContext $context) {
        $this->container = $context->getContainer();
        $this->context = $context;
    }    

    public function prepareQuery($data)
    {
        // @todo throw an exception when the data doesn't have the local key
        $query = $this->getQuery();
        if($this->queryPrepared) {
            $query->setBoundData($this->options['foreign_key'], $data[$this->options['local_key']]);
        } else {
            $query->setTable($this->getModelInstance()->getDBStoreInformation()['quoted_table'])
                ->addFilter($this->options['foreign_key'], $data[$this->options['local_key']]);
            $this->queryPrepared = true;
        }
        return $query;
    }

    public function runSetup()
    {
        if($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = Text::singularize($this->setupTable) . '_id';
        }
        if($this->options['local_key'] == null) {
            $this->options['local_key'] = $this->setupPrimaryKey[0];
        }
    }

    public function assignModel($model, $relationship) {
        $model[$this->options['model']][] = $relationship;
    }        

}
