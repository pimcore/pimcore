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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;

class Filename extends AbstractResolver
{
    /**
     * @var FactoryInterface
     */
    private $modelFactory;

    public function __construct(FactoryInterface $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        $overwrite = (bool)$config->resolverSettings->overwrite;
        $prefix    = (string)$config->resolverSettings->prefix;

        $parent = AbstractObject::getById($parentId);
        if (!$parent) {
            throw new \Exception('parent not found');
        }

        if (!$parent->isAllowed('create')) {
            throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
        }

        if ($overwrite) {
            $objectKey = $rowData[$this->getIdColumn($config)];
        } else {
            $objectKey = $prefix;
        }

        $classId         = $config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $className       = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

        $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;
        $object       = null;

        if ($overwrite) {
            $object = DataObject::getByPath($intendedPath);
            if (!$object instanceof Concrete) {
                //create new object
                $object = $this->modelFactory->build($className);
                $object->setPublished(1);
            } elseif ($object instanceof Concrete and !($object instanceof $className)) {
                //delete the old object it is of a different class
                $object->delete();
                $object = $this->modelFactory->build($className);
                $object->setPublished(1);
            } elseif ($object instanceof Folder) {
                //delete the folder
                $object->delete();
                $object = $this->modelFactory->build($className);
                $object->setPublished(1);
            }

            if ($object) {
                $object->setParent($parent);
                $object->setKey($objectKey);
            }
        } else {
            $object = $this->getAlternativeObject($prefix, $intendedPath, $parent, $className);
        }

        if (!$object) {
            throw new \Exception('failed to resolve object key ' . $objectKey);
        }

        return $object;
    }

    private function getAlternativeObject(string $prefix, string $intendedPath, ElementInterface $parent, string $className)
    {
        $counter   = 1;
        $objectKey = $intendedPath;

        while (DataObject::getByPath($intendedPath) != null) {
            $objectKey    = $prefix . $counter;
            $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;
            $counter++;
        }

        $object = $this->modelFactory->build($className);
        $object->setParent($parent);
        $object->setKey($objectKey);

        return $object;
    }
}
