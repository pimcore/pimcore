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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;

class GetBy extends AbstractResolver
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
        $attribute = $config->resolverSettings->attribute;

        if (!$attribute) {
            throw new \InvalidArgumentException('Attribute is not set');
        }

        $idColumn = $this->getIdColumn($config);
        $cellData = $rowData[$idColumn];

        $classId = $config->classId;
        $classDefinition = ClassDefinition::getById($classId);
        $listClassName = 'Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName() . '\\Listing');

        $list = $this->modelFactory->build($listClassName);

        $list->setCondition($attribute . ' = ' . $list->quote($cellData));
        $list->setLimit(1);
        $list = $list->load();

        if ($list) {
            /** @var ElementInterface|Concrete|Document|Asset $object */
            $object = $list[0];

            if ($object) {
                $parent = $object->getParent();
                if (!$parent->isAllowed('create')) {
                    throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
                }
            }

            return $object;
        }

        throw new \Exception('failed to resolve object where ' . $attribute . ' = ' . $cellData);
    }
}
