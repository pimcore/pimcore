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
use Pimcore\Model\FactoryInterface;

class Fullpath extends AbstractResolver
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
        $createOnDemand = $config->resolverSettings->createOnDemand;
        $createParents  = $config->resolverSettings->createParents;

        $fullpath = $rowData[$this->getIdColumn($config)];
        $object   = DataObject::getByPath($fullpath);

        if (!$object && $createOnDemand) {
            $keyParts  = explode('/', $fullpath);
            $objectKey = $keyParts[count($keyParts) - 1];
            array_pop($keyParts);

            $parentPath = implode('/', $keyParts);

            $parent = DataObject::getByPath($parentPath);
            if (!$parent && $createParents) {
                $parent = DataObject\Service::createFolderByPath($parentPath);
            }

            $classId         = $config->classId;
            $classDefinition = ClassDefinition::getById($classId);
            $className       = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

            $object = $this->modelFactory->build($className);
            $object->setKey($objectKey);
            $object->setParent($parent);
            $object->setPublished(1);
        } else {
            $parent = $object->getParent();
        }

        if (!$parent->isAllowed('create')) {
            throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
        }

        if (!$object) {
            throw new \Exception('failed to resolve object ' . $fullpath);
        }

        return $object;
    }
}
