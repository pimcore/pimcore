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

namespace Pimcore\DataObject\Import\Resolver;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

/**
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
class Id extends AbstractResolver
{
    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        $idColumn = $this->getIdColumn($config);
        if (null === $idColumn) {
            throw new \InvalidArgumentException('ID column is not set');
        }

        $id = $rowData[$idColumn];

        $object = Concrete::getById($id);
        if (!$object) {
            throw new ImportErrorException('Could not resolve object with id ' . $id);
        }

        $classDefinition = ClassDefinition::getById($config->classId);
        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName());

        if (!$object instanceof $className) {
            throw new ImportErrorException('Class mismatch for ID ' . $id);
        }

        $parent = $object->getParent();
        if (!$parent->isAllowed('create')) {
            throw new ImportErrorException('no permission to overwrite object with id ' . $id);
        }

        $this->setObjectType($config, $object, $rowData);

        return $object;
    }
}
