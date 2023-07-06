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
class CleanupFieldcollectionTablesTask implements TaskInterface
{
    private const PIMCORE_FIELDCOLLECTION_CLASS_DIRECTORY =
        PIMCORE_CLASS_DIRECTORY . '/DataObject/Fieldcollection/Data';

    private LoggerInterface $logger;

    private TaskHelper $helper;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->helper = TaskHelper::create();
    }

    public function execute(): void
    {
        if (!is_dir(self::PIMCORE_FIELDCOLLECTION_CLASS_DIRECTORY)) {
            return;
        }

        $mapLowerToCamelCase =
            $this->helper->getDataStructureNamesMapLowerToCamelCase(self::PIMCORE_FIELDCOLLECTION_CLASS_DIRECTORY);

        $db = Db::get();
        $tasks = [
            [
                'localized' => false,
                'prefix' => 'object_collection_',
                'pattern' => "object\_collection\_%",
            ],
        ];
        foreach ($tasks as $task) {
            $prefix = $task['prefix'];
            $pattern = $task['pattern'];
            $tableNames = $db->fetchAllAssociative("SHOW TABLES LIKE '" . $pattern . "'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);
                $this->logger->info($tableName . "\n");

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $fcType = substr($fieldDescriptor, 0, $idx);
                $fcType = $mapLowerToCamelCase[$fcType] ?? $fcType;

                if (!$this->checkIfFcExists($fcType, $tableName)) {
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);

                $isLocalized = false;

                if (str_starts_with($classId, 'localized_')) {
                    $isLocalized = true;
                    $classId = substr($classId, strlen('localized_'));
                }

                $classDefinition = $this->helper->getClassDefintionByClassId($classId, $tableName);
                if (!$classDefinition) {
                    continue;
                }

                $this->helper->cleaningTable($tableName, $classDefinition, $classId, $isLocalized);
            }
        }
    }

    private function checkIfFcExists(string $fcType, string $tableName): bool
    {
        $fcDef = \Pimcore\Model\DataObject\Fieldcollection\Definition::getByKey($fcType);
        if (!$fcDef) {
            $this->logger->error("Fieldcollection '" . $fcType . "' not found. Please check table " . $tableName);
            return false;
        }
        return true;
    }
}
