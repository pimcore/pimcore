<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Logger;
use Pimcore\Model\DataObject;

trait AllowObjectRelationTrait
{
    /**
     * Checks if an object is an allowed relation
     *
     * @param DataObject\AbstractObject $object
     *
     * @return bool
     */
    protected function allowObjectRelation($object)
    {
        $allowedClasses = $this->getClasses();
        $allowed = true;
        if (!$this->getObjectsAllowed()) {
            $allowed = false;
        } elseif ($this->getObjectsAllowed() and count($allowedClasses) > 0) {
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

        if ($object instanceof DataObject\AbstractObject) {
            Logger::debug('checked object relation to target object [' . $object->getId() . '] in field [' . $this->getName() . '], allowed:' . $allowed);
        } else {
            Logger::debug('checked object relation to target in field [' . $this->getName() . '], not allowed, target ist not an object');
            Logger::debug($object);
        }

        return $allowed;
    }
}
