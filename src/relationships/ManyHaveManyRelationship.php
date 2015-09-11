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

use ntentan\nibii\Nibii;
use ntentan\utils\Text;

class ManyHaveManyRelationship extends \ntentan\nibii\Relationship
{
    protected $type = self::MANY_HAVE_MANY;
    
    private function getJunctionModel()
    {
        $class = $this->options['junction_model'];
        return new $class();
    }
    
    public function getQuery($data)
    {
        $junctionModel = $this->getJunctionModel();
        $filter = $junctionModel->fields($this->options['junction_foreign_key'])
            ->filterBy($this->options['junction_local_key'], $data[$this->options['local_key']])
            ->fetch();
        $foreignKeys = [];
        foreach($filter->toArray() as $foreignItem) {
            $foreignKeys[] = $foreignItem[$this->options['junction_foreign_key']];
        }
        $query = (new \ntentan\nibii\QueryParameters($this->getModelInstance()))
            ->addFilter($this->options['foreign_key'], $foreignKeys);
        return $query;
    }

    public function setup($model, $table, $primaryKey)
    {
        if(isset($this->options['through'])) {
            $junctionModelName = $this->options['through'];
        } else {
            $junctionModelName = Nibii::joinModels($model, $this->options['model']);
        }
        $this->options['junction_model'] = $junctionModelName;
        
        $foreignModel = $this->getModelInstance();
        if($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = $foreignModel->getDescription()->getPrimaryKey()[0];
        } 
        
        if($this->options['local_key'] == null) {
            $this->options['local_key'] = $primaryKey[0];
        }
        
        if(!isset($this->options['junction_local_key'])) {
            $this->options['junction_local_key'] = 
                Text::singularize($table) . '_id';
        }
        
        if(!isset($this->options['junction_foreign_key'])) {
            $this->options['junction_foreign_key'] = 
                Text::singularize($foreignModel->getTable()) . '_id';
        }
    }
}