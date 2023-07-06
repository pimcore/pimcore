<?php
declare(strict_types=1);

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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CleanupBrickTablesTask implements TaskInterface
{
    private const PIMCORE_OBJECTBRICK_CLASS_DIRECTORY = PIMCORE_CLASS_DIRECTORY . '/DataObject/Objectbrick/Data';

    private LoggerInterface $logger;

    private TaskHelper $helper;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->helper = TaskHelper::create();
    }

    public function execute(): void
    {
        if (!is_dir(self::PIMCORE_OBJECTBRICK_CLASS_DIRECTORY)) {
            return;
        }

        $mapLowerToCamelCase =
            $this->helper->getDataStructureNamesMapLowerToCamelCase(self::PIMCORE_OBJECTBRICK_CLASS_DIRECTORY);

        $db = Db::get();
        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            $tableNames = $db->fetchAllAssociative("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (str_starts_with($tableName, 'object_brick_localized_query_')) {
                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $brickType = substr($fieldDescriptor, 0, $idx);
                $brickType = $mapLowerToCamelCase[$brickType] ?? $brickType;

                if (!$this->checkIfBrickExists($brickType, $tableName)) {
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);
                $classDefinition = $this->helper->getClassDefintionByClassId($classId, $tableName);
                if (!$classDefinition) {
                    continue;
                }

                $this->helper->cleaningTable($tableName, $classDefinition, $classId);
            }
        }
    }

    private function checkIfBrickExists(string $brickType, string $tableName): bool
    {
        $brickDef = Definition::getByKey($brickType);
        if (!$brickDef) {
            $this->logger->error("Brick '" . $brickType . "' not found. Please check table " . $tableName);
            return false;
        }
        return true;
    }
}
