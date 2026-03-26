<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CleanupClassificationstoreTablesTask implements TaskInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $db = Db::get();
        $tableTypes = ['object_classificationstore_data', 'object_classificationstore_groups'];
        foreach ($tableTypes as $tableType) {
            $prefix = $tableType . '_';
            $tableNames = $db->fetchAllAssociative("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);
                $classId = substr($tableName, strlen($prefix));

                $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
                if (!$classDefinition) {
                    $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);

                    continue;
                }

                $fieldsQuery = 'SELECT DISTINCT fieldname FROM ' . $tableName;
                $fieldNames = $db->fetchFirstColumn($fieldsQuery);

                foreach ($fieldNames as $fieldName) {
                    $fieldDef = $classDefinition->getFieldDefinition($fieldName);

                    if (!$fieldDef) {
                        $this->logger->info("Field '" . $fieldName . "' of class '" . $classId . "' does not exist anymore. Cleaning " . $tableName);
                        $db->delete($tableName, ['fieldname' => $fieldName]);
                    }
                }
            }
        }
    }
}
