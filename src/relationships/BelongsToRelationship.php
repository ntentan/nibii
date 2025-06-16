<?php
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
    protected RelationshipType $type = RelationshipType::BELONGS_TO;
    private array $relatedData;

    public function doprepareQuery($data)
    {
        if (!array_key_exists($this->options['local_key'], $data)) {
            throw new FieldNotFoundException("Field {$this->options['local_key']} not found for belongs to relationship query.");
        }
        if (!isset($data[$this->options['local_key']])) {
            return;
        }
        $query = $this->createQuery();
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
        $model = ORMContext::getInstance()->getModelFactory()->createModel($this->options['model'], RelationshipType::BELONGS_TO);
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
        //$wrapper[$this->options['model']] = $this->relatedData;
    }
}
