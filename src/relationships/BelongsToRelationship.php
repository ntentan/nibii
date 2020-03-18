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

use ntentan\nibii\exceptions\FieldNotFoundException;
use ntentan\nibii\ORMContext;
use ntentan\nibii\Relationship;
use ntentan\utils\Text;

/**
 * Represents a one to many belongs to relationship.
 */
class BelongsToRelationship extends Relationship
{
    protected $type = self::BELONGS_TO;
    private $relatedData;

    public function prepareQuery($data)
    {
        if (!array_key_exists($this->options['local_key'], $data)) {
            throw new FieldNotFoundException("Field {$this->options['local_key']} not found for belongs to relationship query.");
        }
        if (!isset($data[$this->options['local_key']])) {
            return;
        }
        $query = $this->getQuery();
        if ($this->queryPrepared) {
            $query->setBoundData($this->options['foreign_key'], $data[$this->options['local_key']]);
        } else {
            $query->setTable($this->getModelInstance()->getDBStoreInformation()['quoted_table'])
                ->addFilter($this->options['foreign_key'], $data[$this->options['local_key']])
                ->setFirstOnly(true);
            $this->queryPrepared = true;
        }

        return $query;
    }

    public function runSetup()
    {
        $model = ORMContext::getInstance()->getModelFactory()->createModel($this->options['model'], self::BELONGS_TO);
        $table = $model->getDBStoreInformation()['table'];
        if ($this->options['foreign_key'] == null) {
            $this->options['foreign_key'] = $model->getDescription()->getPrimaryKey()[0];
        }
        if ($this->options['local_key'] == null) {
            $this->options['local_key'] = Text::singularize($table).'_id';
        }
    }

    public function preSave(&$wrapper, $value)
    {
        if(!$value->save()) {
            $this->invalidFields = $value->getInvalidFields();
        }
        $wrapper[$this->options['local_key']] = $value[$this->options['foreign_key']];
        unset($wrapper[$this->options['model']]);
    }

    public function postSave(&$wrapper)
    {
        $wrapper[$this->options['model']] = $this->relatedData;
    }
}
