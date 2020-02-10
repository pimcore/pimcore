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

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Helper\ImportClassResolver;
use Pimcore\Model\DataObject\ImportDataServiceInterface;
use Pimcore\Model\FactoryInterface;

class Fullpath extends AbstractResolver
{
    /**
     * @var FactoryInterface
     */
    private $modelFactory;

    /**
     * @var ImportClassResolver
     */
    private $classResolver;

    public function __construct(FactoryInterface $modelFactory, ImportClassResolver $classResolver)
    {
        $this->modelFactory = $modelFactory;
        $this->classResolver = $classResolver;
    }

    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        $createOnDemand = (bool)$config->resolverSettings->createOnDemand;
        $createParents = (bool)$config->resolverSettings->createParents;
        $skipIfExists = (bool)$config->resolverSettings->skipIfExists;
        $service = $this->classResolver->resolveClassOrService($config->resolverSettings->phpClassOrService);

        $fullpath = $rowData[$this->getIdColumn($config)];
        $object = DataObject::getByPath($fullpath);

        if ($object && $skipIfExists) {
            throw new ImportWarningException('skipped object exists: ' . $object->getFullPath());
        }

        if (!$object && $createOnDemand) {

            if ($service instanceof ImportDataServiceInterface) {
                $object = $service->populate($config, null, $rowData, ['parentId' => $parentId]);
            } else {
                $keyParts = explode('/', $fullpath);
                $objectKey = $keyParts[count($keyParts) - 1];
                array_pop($keyParts);

                $parentPath = implode('/', $keyParts);

                $parent = DataObject::getByPath($parentPath);
                if (!$parent && $createParents) {
                    $parent = DataObject\Service::createFolderByPath($parentPath);
                }

                $classId = $config->classId;
                $classDefinition = ClassDefinition::getById($classId);
                $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

                $object = $this->modelFactory->build($className);
                $object->setKey($objectKey);
                $object->setParent($parent);
                $object->setPublished(1);
            }
        } else {
            if ($object && !$service) {
                $parent = $object->getParent();
            } elseif($object && $service){
                $object = $service->populate($config, $object, $rowData, ['parentId' => $parentId]);
            } else {
                throw new ImportErrorException('failed to resolve object ' . $fullpath);
            }
        }

        if (!$parent->isAllowed('create')) {
            throw new ImportErrorException('not allowed to import into folder ' . $parent->getFullPath());
        }

        if (!$object) {
            throw new ImportErrorException('failed to resolve object ' . $fullpath);
        }

        $this->setObjectType($config, $object, $rowData);

        return $object;
    }
}
