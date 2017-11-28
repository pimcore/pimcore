<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ImportResolver;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;

class Filename extends AbstractResolver
{
    /**
     * constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->overwrite = $config->resolverSettings->overwrite;
        $this->prefix = $config->resolverSettings->prefix;
    }

    /**
     * @param $parentId
     * @param $rowData
     *
     * @return static
     *
     * @throws \Exception
     */
    public function resolve($parentId, $rowData)
    {
        $parent = AbstractObject::getById($parentId);
        if (!$parent) {
            throw new \Exception('parent not found');
        }
        if (!$parent->isAllowed('create')) {
            throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
        }

        if ($this->overwrite) {
            $objectKey = $rowData[$this->getIdColumn()];
        } else {
            $objectKey = $this->prefix;
        }
        $classId = $this->config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

        $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;
        $container = \Pimcore::getContainer();

        if ($this->overwrite) {
            $object = DataObject::getByPath($intendedPath);
            if (!$object instanceof Concrete) {
                //create new object
                $object = $container->get('pimcore.model.factory')->build($className);
                $object->setPublished(1);
            } elseif ($object instanceof Concrete and !($object instanceof $className)) {
                //delete the old object it is of a different class
                $object->delete();
                $object = $container->get('pimcore.model.factory')->build($className);
                $object->setPublished(1);
            } elseif ($object instanceof Folder) {
                //delete the folder
                $object->delete();
                $object = $container->get('pimcore.model.factory')->build($className);
                $object->setPublished(1);
            }

            if ($object) {
                $object->setParent($parent);
                $object->setKey($objectKey);
            }
        } else {
            $this->getAlternativeObject($intendedPath, $parent, $className);
        }
        if (!$object) {
            throw new \Exception('failed to resolve object key ' . $objectKey);
        }

        return $object;
    }

    /**
     * @param $intendedPath
     * @param $parent
     * @param $className
     *
     * @return string
     */
    public function getAlternativeObject($intendedPath, $parent, $className)
    {
        $counter = 1;
        $objectKey = $intendedPath;
        while (DataObject::getByPath($intendedPath) != null) {
            $objectKey = $this->prefix . $counter;
            $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;
            $counter++;
        }
        $container = \Pimcore::getContainer();
        $object = $container->get('pimcore.model.factory')->build($className);
        $object->setParent($parent);
        $object->setKey($objectKey);

        return $objectKey;
    }
}
