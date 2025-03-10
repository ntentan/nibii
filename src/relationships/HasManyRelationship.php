<?php
namespace ntentan\nibii\relationships;

use ntentan\nibii\exceptions\NibiiException;
use ntentan\nibii\Relationship;
use ntentan\utils\Text;

class HasManyRelationship extends Relationship
{
    protected RelationshipType $type = RelationshipType::HAS_MANY;
    private array $tempData;

    public function doprepareQuery($data)
    {
        // @todo throw an exception when the data doesn't have the local key
        $query = $this->createQuery();
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
