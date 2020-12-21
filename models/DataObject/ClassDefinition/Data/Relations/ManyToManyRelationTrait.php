<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element\DirtyIndicatorInterface;

trait ManyToManyRelationTrait
{
    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     */
    public function save($container, $params = [])
    {
        if (!isset($params['forceSave']) || $params['forceSave'] !== true) {
            if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $container instanceof DirtyIndicatorInterface) {
                if ($container instanceof DataObject\Localizedfield) {
                    if ($container->getObject() instanceof DirtyIndicatorInterface) {
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
        }

        $data = $this->getDataFromObjectParam($container, $params);

        parent::save($container, $params);
    }
}
