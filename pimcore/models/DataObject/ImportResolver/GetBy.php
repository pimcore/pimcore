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

use Pimcore\Model\DataObject\ClassDefinition;

class GetBy extends AbstractResolver
{
    /**
     * constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->attribute = $config->resolverSettings->attribute;

        if (!$this->attribute) {
            throw new \Exception('attribute not set');
        }
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
        $idColumn = $this->getIdColumn();
        $cellData = $rowData[$idColumn];

        $classId = $this->config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $listClassName = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName() . '\\Listing');

        $list = \Pimcore::getContainer()->get('pimcore.model.factory')->build($listClassName);

        $list->setCondition($this->attribute . ' = ' . $list->quote($cellData));
        $list->setLimit(1);
        $list = $list->load();

        if ($list) {
            $object = $list[0];
            if ($object) {
                $parent = $object->getParent();
                if (!$parent->isAllowed('create')) {
                    throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
                }
            }

            return $object;
        }


        throw new \Exception('failed to resolve object where ' . $this->attribute . " = " . $cellData);

    }
}
