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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Helper\ImportClassResolver;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ImportDataServiceInterface;
use Pimcore\Model\DataObject\Listing;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\FactoryInterface;

class GetBy extends AbstractResolver
{
    /**
     * @var FactoryInterface
     */
    private $modelFactory;

    /**
     * GetBy constructor.
     *
     * @param FactoryInterface $modelFactory
     */
    public function __construct(FactoryInterface $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    /**
     * @param \stdClass $config
     * @param int $parentId
     * @param array $rowData
     *
     * @return Asset|Concrete|Document|ElementInterface
     *
     * @throws \Exception
     */
    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        $attribute = (string)$config->resolverSettings->attribute;
        $skipIfExists = filter_var($config->resolverSettings->skipIfExists ?? false, FILTER_VALIDATE_BOOLEAN);
        $createOnDemand = filter_var($config->resolverSettings->createOnDemand ?? false, FILTER_VALIDATE_BOOLEAN);

        $service = ImportClassResolver::resolveClassOrService($config->resolverSettings->phpClassOrService);

        if (!$attribute) {
            throw new \InvalidArgumentException('Attribute is not set');
        }

        $idColumn = $this->getIdColumn($config);
        $cellData = $rowData[$idColumn];

        $classId = $config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $listClassName = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName() . '\\Listing');

        /** @var Listing $list */
        $list = $this->modelFactory->build($listClassName);

        $list->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_FOLDER, AbstractObject::OBJECT_TYPE_VARIANT]);
        $list->setCondition($attribute . ' = ' . $list->quote($cellData));
        $list->setLimit(1);
        $list = $list->load();

        if ($list) {
            /** @var Concrete|Document|Asset $object */
            $object = $list[0];

            if ($object) {
                $parent = $object->getParent();
                if (!$parent->isAllowed('create')) {
                    throw new ImportErrorException('not allowed to import into folder ' . $parent->getFullPath());
                }
            }

            if ($skipIfExists && $object && !($service instanceof ImportDataServiceInterface)) {
                throw new ImportWarningException('skipped row where '. $attribute . ' = ' . $cellData . ' ( existing object-id:' . $object->getId() . ' )');
            }

            if ($service instanceof ImportDataServiceInterface) {
                $object = $service->populate($config, $object, $rowData, ['parentId' => $parentId]);
            } else {
                $this->setObjectType($config, $object, $rowData);
            }

            return $object;
        } elseif ($createOnDemand) {
            if ($service instanceof ImportDataServiceInterface) {
                $object = $service->populate($config, null, $rowData, ['parentId' => $parentId]);
            } else {
                $classId = $config->classId;
                $classDefinition = ClassDefinition::getById($classId);
                $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

                $object = $this->modelFactory->build($className);
                $object->setKey(Service::getValidKey($cellData, 'object'));
                $object->setParentId($parentId);
                $object->setPublished(1);

                $object = $this->setObjectType($config, $object, $rowData);
            }

            return $object;
        }

        throw new ImportErrorException('failed to resolve object where ' . $attribute . ' = ' . $cellData);
    }
}
