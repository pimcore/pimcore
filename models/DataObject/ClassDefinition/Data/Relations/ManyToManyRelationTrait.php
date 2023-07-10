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
     * Unless forceSave is set to true, this method will check if the field is dirty and skip the save if not
     *
     * @param object $object
     * @param array $params
     * @return bool
     */
    protected function skipSaveCheck(object $object, array $params = []): bool
    {
        $forceSave = $params['forceSave'] ?? false;

        if ($forceSave === false) {
            if (!DataObject::isDirtyDetectionDisabled() && $object instanceof DirtyIndicatorInterface) {
                if ($object instanceof DataObject\Localizedfield) {
                    if ($object->getObject() instanceof DirtyIndicatorInterface && !$object->hasDirtyFields()) {
                        return true;
                    }
                }
                if ($this->supportsDirtyDetection()) {
                    if (!$object->isFieldDirty($this->getName())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($container, $params = [])
    {
        if ($this->skipSaveCheck($container, $params)) {
            return;
        }

        parent::save($container, $params);
    }
}
