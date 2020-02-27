<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Model\DataObject;

trait ManyToManyRelationTrait
{
    /**
     * TODO: move validation to checkValidity & throw exception in Pimcore 7
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     */
    public function save($object, $params = [])
    {
        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof DataObject\DirtyIndicatorInterface) {
            if ($object instanceof DataObject\Localizedfield) {
                if ($object->getObject() instanceof DataObject\DirtyIndicatorInterface) {
                    if (!$object->hasDirtyFields()) {
                        return;
                    }
                }
            } else {
                if ($this->supportsDirtyDetection()) {
                    if (!$object->isFieldDirty($this->getName())) {
                        return;
                    }
                }
            }
        }

        $data = $this->getDataFromObjectParam($object, $params);

        if (!$object instanceof DataObject\DirtyIndicatorInterface || $object->isFieldDirty($this->getName()) || $object->isFieldDirty('_self')) {
            $this->filterMultipleAssignments($data, $object, $params);
        }

        parent::save($object, $params);
    }
}
