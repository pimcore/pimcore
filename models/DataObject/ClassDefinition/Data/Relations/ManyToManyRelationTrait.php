<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element\DirtyIndicatorInterface;

trait ManyToManyRelationTrait
{
    /**
     * TODO: move validation to checkValidity & throw exception in Pimcore 10
     *
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     */
    public function save($container, $params = [])
    {
        if (!isset($params['forceSave']) || $params['forceSave'] !== true) {
            if (!DataObject::isDirtyDetectionDisabled() && $container instanceof DirtyIndicatorInterface) {
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
        $this->filterMultipleAssignments($data, $container, $params);

        parent::save($container, $params);
    }
}
