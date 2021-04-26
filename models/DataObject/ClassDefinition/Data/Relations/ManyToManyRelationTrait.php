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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element\DirtyIndicatorInterface;

trait ManyToManyRelationTrait
{
    /**
     * {@inheritdoc}
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

        parent::save($container, $params);
    }
}
