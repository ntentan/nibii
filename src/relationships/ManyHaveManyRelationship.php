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
use ntentan\nibii\ORMContext;
use ntentan\nibii\Relationship;
use ntentan\utils\Text;

class ManyHaveManyRelationship extends Relationship
{
    protected $type = self::MANY_HAVE_MANY;
    private $tempdata;
    private $junctionModelInstance;

    public function prepareQuery($data)
    {
        $junctionModelClass = $this->options['junction_model'];
        $junctionModel = new $junctionModelClass();
        $filter = $junctionModel->fields($this->options['junction_foreign_key'])
                ->filterBy($this->options['junction_local_key'], $data[$this->options['local_key']])
                ->fetch();
        if ($filter->count() == 0) {
            return;
        }
        $foreignKeys = [];
        foreach ($filter->toArray() as $foreignItem) {
            $foreignKeys[] = $foreignItem[$this->options['junction_foreign_key']];
        }

        // @todo throw an exception when the data doesn't have the local key
        $query = $this->getQuery();
        if ($this->queryPrepared) {
            $query->setBoundData($this->options['foreign_key'], $foreignKeys);
        } else {
            $query = $this->getQuery()
                    ->setTable($this->getModelInstance()->getDBStoreInformation()['quoted_table'])
                    ->addFilter($this->options['foreign_key'], $foreignKeys);
            $this->queryPrepared = true;
        }

        return $query;
    }

    public function runSetup()
    {
        $context = ORMContext::getInstance();
        if (isset($this->options['through'])) {
            $junctionModelName = $context->getModelFactory()->getClassName($this->options['through']);
        } else {
            $junctionModelName = $context->getModelFactory()->getJunctionClassName($this->setupName, $this->options['model']);
        }
        $this->options['junction_model'] = $junctionModelName;

        $foreignModel = $context->load($this->options['model']);
        if ($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = $foreignModel->getDescription()->getPrimaryKey()[0];
        }

        if ($this->options['local_key'] == null) {
            $this->options['local_key'] = $this->setupPrimaryKey[0];
        }

        if (!isset($this->options['junction_local_key'])) {
            $this->options['junction_local_key'] = Text::singularize($this->setupTable).'_id';
        }

        if (!isset($this->options['junction_foreign_key'])) {
            $this->options['junction_foreign_key'] = Text::singularize($foreignModel->getDBStoreInformation()['table']).'_id';
        }
    }

    public function preSave(&$wrapper, $value)
    {
        $this->tempdata = $wrapper[$this->options['model']];
        $junctionModelClass = $this->options['junction_model'];
        $this->junctionModelInstance = new $junctionModelClass();
        $this->junctionModelInstance->delete([
            $this->options['junction_local_key'] => $wrapper[$this->options['local_key']],
        ]);
        unset($wrapper[$this->options['model']]);
    }

    public function postSave(&$wrapper)
    {
        $jointModelRecords = [];
        foreach ($this->tempdata as $relatedRecord) {
            if (!$relatedRecord->save()) {
                $this->invalidFields =$this->tempdata->getInvalidFields();
            }
            $jointModelRecords[] = [
                $this->options['junction_local_key']   => $wrapper[$this->options['local_key']],
                $this->options['junction_foreign_key'] => $relatedRecord[$this->options['foreign_key']],
            ];
        }
        $this[$this->options['model']] = $this->tempdata;
        $this->junctionModelInstance->setData($jointModelRecords);
        $this->junctionModelInstance->save();
    }
}
