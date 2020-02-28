<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;

trait ManyToManyRelationTrait
{
    /**
     * TODO: move validation to checkValidity & throw exception in Pimcore 7
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     */
    public function save($container, $params = [])
    {
        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $container instanceof DataObject\DirtyIndicatorInterface) {
            if ($container instanceof DataObject\Localizedfield) {
                if ($container->getObject() instanceof DataObject\DirtyIndicatorInterface) {
                    if (!$container->hasDirtyFields()) {
                        return;
                    }
                }
            } else {
                if ($this->supportsDirtyDetection()) {
                    if (!$container->isFieldDirty($this->getName())) {
                        return;
                    }
                }
            }
        }

        $data = $this->getDataFromObjectParam($container, $params);
        $this->filterMultipleAssignments($data, $container, $params);

        parent::save($container, $params);
    }
}
