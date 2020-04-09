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
use Psr\Log\LoggerInterface;

final class CleanupClassificationstoreTablesTask implements TaskInterface
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
        $tableTypes = ['object_classificationstore_data', 'object_classificationstore_groups'];
        foreach ($tableTypes as $tableType) {
            $prefix = $tableType . '_';
            $tableNames = $db->fetchAll("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);
                $classId = substr($tableName, strlen($prefix));

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
                        $this->logger->info("Field '" . $fieldName . "' of class '" . $classId . "' does not exist anymore. Cleaning " . $tableName);
                        $db->deleteWhere($tableName, 'fieldname = ' . $db->quote($fieldName));
                    }
                }
            }
        }
    }
}
