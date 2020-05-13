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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Psr\Log\LoggerInterface;

final class CleanupBrickTablesTask implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $db = Db::get();
        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            $tableNames = $db->fetchAll("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (strpos($tableName, 'object_brick_localized_query_') === 0) {
                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $brickType = substr($fieldDescriptor, 0, $idx);

                $brickDef = Definition::getByKey($brickType);
                if (!$brickDef) {
                    $this->logger->error("Brick '" . $brickType . "' not found. Please check table " . $tableName);
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);

                $classDefinition = ClassDefinition::getById($classId);
                if (!$classDefinition) {
                    $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);
                    continue;
                }

                $fieldsQuery = 'SELECT fieldname FROM ' . $tableName . ' GROUP BY fieldname';
                $fieldNames = $db->fetchCol($fieldsQuery);

                foreach ($fieldNames as $fieldName) {
                    $fieldDef = $classDefinition->getFieldDefinition($fieldName);
                    if (!$fieldDef) {
                        $lfDef = $classDefinition->getFieldDefinition('localizedfields');
                        if ($lfDef instanceof ClassDefinition\Data\Localizedfields) {
                            $fieldDef = $lfDef->getFieldDefinition($fieldName);
                        }
                    }

                    if (!$fieldDef) {
                        $this->logger->info("Field '" . $fieldName . "' of class '" . $classId . "' does not exist anymore. Cleaning " . $tableName);
                        $db->deleteWhere($tableName, 'fieldname = ' . $db->quote($fieldName));
                    }
                }
            }
        }
    }
}
