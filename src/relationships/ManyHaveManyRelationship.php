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
use ntentan\nibii\QueryParameters;

class ManyHaveManyRelationship extends \ntentan\nibii\Relationship {

    protected $type = self::MANY_HAVE_MANY;
    
    public function __construct(ORMContext $context) {
        $this->container = $context->getContainer();
        $this->context = $context;
    }    

    public function getQuery($data) {
        $junctionModel = $this->container->resolve($this->options['junction_model']);
        $filter = $junctionModel->fields($this->options['junction_foreign_key'])
            ->filterBy($this->options['junction_local_key'], $data[$this->options['local_key']])
            ->fetch();
        if($filter->count() == 0) {
            return null;
        }
        $foreignKeys = [];
        foreach ($filter->toArray() as $foreignItem) {
            $foreignKeys[] = $foreignItem[$this->options['junction_foreign_key']];
        }
        $query = (new QueryParameters($this->getModelInstance()->getDBStoreInformation()['quoted_table']))
                ->addFilter($this->options['foreign_key'], $foreignKeys);
        return $query;
    }

    public function runSetup() {
        if (isset($this->options['through'])) {
            $junctionModelName = 
                $this->context->getClassName($this->options['through']);
        } else {
            $junctionModelName = 
                $this->context->joinModels($this->setupName, $this->options['model']);
        }
        $this->options['junction_model'] = $junctionModelName;

        $foreignModel = $this->context->load($this->options['model']);
        if ($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = 
                $foreignModel->getDescription()->getPrimaryKey()[0];
        }

        if ($this->options['local_key'] == null) {
            $this->options['local_key'] = $this->setupPrimaryKey[0];
        }

        if (!isset($this->options['junction_local_key'])) {
            $this->options['junction_local_key'] = 
                Text::singularize(explode('.', $this->setupTable)[1]) . '_id';
        }

        if (!isset($this->options['junction_foreign_key'])) {
            $this->options['junction_foreign_key'] = 
                Text::singularize($foreignModel->getDBStoreInformation()['table']) . '_id';
        }
    }

}
