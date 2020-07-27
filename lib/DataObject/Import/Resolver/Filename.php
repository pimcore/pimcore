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

namespace Pimcore\DataObject\Import\Resolver;

use const FILTER_VALIDATE_BOOLEAN;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Helper\ImportClassResolver;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\ImportDataServiceInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
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
        $overwrite = filter_var($config->resolverSettings->overwrite ?? false, FILTER_VALIDATE_BOOLEAN);
        $skipIfExists = filter_var($config->resolverSettings->skipIfExists ?? false, FILTER_VALIDATE_BOOLEAN);
        $prefix = (string)$config->resolverSettings->prefix;
        $service = ImportClassResolver::resolveClassOrService($config->resolverSettings->phpClassOrService);

        $parent = AbstractObject::getById($parentId);
        if (!$parent) {
            throw new ImportErrorException('parent not found');
        }

        if (!$parent->isAllowed('create')) {
            throw new ImportErrorException('not allowed to import into folder ' . $parent->getFullPath());
        }

        $object = null;

        $classId = $config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

        $objectKey = $rowData[$this->getIdColumn($config)];
        $objectKey = Service::getValidKey($objectKey, 'object');
        $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;

        if (!$overwrite) {
            if (AbstractObject::getByPath($intendedPath)) {
                $objectKey = $prefix;
            } else {
                $object = $this->modelFactory->build($className);
                $object->setParent($parent);
                $object->setKey($objectKey);
            }
        }

        if (!$object) {
            if ($object = DataObject::getByPath($intendedPath) && $skipIfExists) {
                throw new ImportWarningException('skipped filename exists: ' . $parent->getFullPath() . '/' . $objectKey);
            }
        }

        if ($overwrite && !$service) {
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
        } elseif ($overwrite && $service) {
            $object = DataObject::getByPath($intendedPath);

            $object = $service->populate($config, $object, $rowData, [
                'override' => $overwrite,
            ]);
        } else {
            if ($service instanceof ImportDataServiceInterface) {
                $object = $service->populate($config, null, $rowData, [
                    'parentId' => $parentId,
                    'prefix' => $prefix,
                    'intendedPath' => $intendedPath,
                    'parent' => $parent,
                    'classname' => $className,
                ]);
            } else {
                if (!$object) {
                    $object = $this->getAlternativeObject($prefix, $intendedPath, $parent, $className);
                }
            }
        }

        if (!$object) {
            throw new ImportErrorException('failed to resolve object key ' . $objectKey);
        }

        $this->setObjectType($config, $object, $rowData);

        return $object;
    }

    private function getAlternativeObject(string $prefix, string $intendedPath, ElementInterface $parent, string $className)
    {
        $counter = 1;
        $objectKey = $intendedPath;

        while (DataObject::getByPath($intendedPath) != null) {
            $objectKey = $prefix . $counter;
            $intendedPath = $parent->getRealFullPath() . '/' . $objectKey;
            $counter++;
        }

        $object = $this->modelFactory->build($className);
        $object->setParent($parent);
        $object->setKey($objectKey);

        return $object;
    }
}
