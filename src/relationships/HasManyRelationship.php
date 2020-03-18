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

namespace ntentan\nibii\relationships;

use ntentan\nibii\exceptions\NibiiException;
use ntentan\nibii\Relationship;
use ntentan\utils\Text;

class HasManyRelationship extends Relationship
{
    protected $type = self::HAS_MANY;
    private $tempData;

    public function prepareQuery($data)
    {
        // @todo throw an exception when the data doesn't have the local key
        $query = $this->getQuery();
        if(!$this->queryPrepared) {
            $query->setTable($this->getModelInstance()->getDBStoreInformation()['quoted_table'])
                ->addFilter($this->options['foreign_key'], $data[$this->options['local_key']] ?? null);
            $this->queryPrepared = true;
            return $query;
        }
        if(isset($data[$this->options['local_key']])) {
            $query->setBoundData($this->options['foreign_key'], $data[$this->options['local_key']]);
        }
        return $query;
    }

    public function runSetup()
    {
        if ($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = Text::singularize($this->setupTable).'_id';
        }
        if ($this->options['local_key'] == null) {
            $this->options['local_key'] = $this->setupPrimaryKey[0];
        }
    }

    public function preSave(&$wrapper, $value)
    {
        $this->tempData = $wrapper[$this->options['model']];
        unset($wrapper[$this->options['model']]);
    }

    public function postSave(&$wrapper)
    {
        $records = $this->tempData->getData();
        foreach($records as $i => $relatedRecord) {
            $records[$i][$this->options['foreign_key']] = $wrapper[$this->options['local_key']];
        }
        $this->tempData->setData($records);
        if(!$this->tempData->save()) {
            $this->invalidFields = $this->tempData->getInvalidFields();
        }
        $wrapper[$this->options['model']] = $this->tempData;
    }
}
