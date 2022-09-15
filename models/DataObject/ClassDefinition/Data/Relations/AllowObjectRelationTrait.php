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

use Pimcore\Logger;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
trait AllowObjectRelationTrait
{
    /**
     * Checks if an object is an allowed relation
     *
     * @internal
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return bool
     *
     * @throws \Exception
     *
     */
    protected function allowObjectRelation($object, $params = [])
    {
        if (!$object instanceof DataObject\AbstractObject || $object->getId() <= 0) {
            return false;
        }

        $allowedClasses = $this->getClasses();
        $allowed = true;
        if (!$this->getObjectsAllowed()) {
            $allowed = false;
        } elseif (count($allowedClasses) > 0) {
            $allowedClassnames = [];
            foreach ($allowedClasses as $c) {
                $allowedClassnames[] = $c['classes'];
            }
            //check for allowed classes
            if ($object instanceof DataObject\Concrete) {
                $classname = $object->getClassName();
                if (!in_array($classname, $allowedClassnames)) {
                    $allowed = false;
                }
            } elseif ($object instanceof DataObject\Folder) {
                if (!in_array('folder', $allowedClassnames)) {
                    $allowed = false;
                }
            } else {
                $allowed = false;
            }
        } else {
            //don't check if no allowed classes set
        }

        $sqlCondition = $this->getSqlCondition();
        if ($allowed && $sqlCondition) {
            $sqlCondition = \Pimcore::getContainer()->get('twig')
                ->createTemplate($sqlCondition)
                ->render(['object' => $params['object']]);

            $listing = $object->getList();
            $listing->addConditionParam('o_id = ?', [$object->getId()]);
            $listing->addConditionParam($sqlCondition);
            if ( count($listing) === 0 ) {
                $allowed = false;
            }
        }

        Logger::debug('checked object relation to target object ['.$object->getId().'] in field ['.$this->getName().'], allowed:'.$allowed);

        return $allowed;
    }
}
