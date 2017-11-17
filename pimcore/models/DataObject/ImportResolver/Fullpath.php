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
use Pimcore\Model\DataObject\ClassDefinition;

class Fullpath extends AbstractResolver
{
    /**
     * constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->createOnDemand = $config->resolverSettings->createOnDemand;
        $this->createParents = $config->resolverSettings->createParents;
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
        $fullpath = $rowData[$this->getIdColumn()];
        $object = DataObject::getByPath($fullpath);
        if (!$object && $this->createOnDemand) {
            $keyParts = explode('/', $fullpath);
            $objectKey = $keyParts[count($keyParts) - 1];
            array_pop($keyParts);

            $parentPath = implode('/', $keyParts);

            $parent = DataObject::getByPath($parentPath);
            if (!$parent && $this->createOnDemand) {
                $parent = DataObject\Service::createFolderByPath($parentPath);
            }

            $classId = $this->config->classId;
            $classDefinition = ClassDefinition::getById($classId);
            $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());
            $object = \Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
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
            throw new \Exception('failed to resolve object key ' . $objectKey);
        }

        return $object;
    }
}
