<?php


declare(strict_types=1);

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

namespace Pimcore\DataObject\ClassDefinition\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Pimcore\Model;

abstract class AbstractSchemaBuilder implements SchemaBuilderInterface
{
    /**
     * @param Table                                 $table
     * @param Model\DataObject\ClassDefinition\Data $fd
     * @param array                                 $schemaColumns
     */
    protected function addColumnsToTable(Table $table, Model\DataObject\ClassDefinition\Data $fd, array $schemaColumns)
    {
        foreach ($schemaColumns as $col) {
            if (!$col instanceof Column) {
                throw new \InvalidArgumentException(sprintf('Expected Type %s, got type %s', Column::class,
                    get_class($col)));
            }

            $table->addColumn($col->getName(), $col->getType()->getName(), $col->toArray());
        }

        if ($fd->getIndex()) {
            $indexFields = [];

            foreach ($schemaColumns as $column) {
                $indexFields[] = $column->getName();
            }

            if ($fd->getUnique()) {
                $table->addUniqueIndex($indexFields);
            } else {
                $table->addIndex($indexFields);
            }
        }
    }
}
