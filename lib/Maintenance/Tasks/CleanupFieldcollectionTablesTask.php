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

final class CleanupFieldcollectionTablesTask implements TaskInterface
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
            $tableNames = $db->fetchAll("SHOW TABLES LIKE '" . $pattern . "'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);
                $this->logger->info($tableName . "\n");

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $fcType = substr($fieldDescriptor, 0, $idx);

                $fcDef = \Pimcore\Model\DataObject\Fieldcollection\Definition::getByKey($fcType);
                if (!$fcDef) {
                    $this->logger->error("Fieldcollection '" . $fcType . "' not found. Please check table " . $tableName);
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);

                $isLocalized = false;

                if (strpos($classId, 'localized_') === 0) {
                    $isLocalized = true;
                    $classId = substr($classId, strlen('localized_'));
                }

                $classDefinition = ClassDefinition::getById($classId);
                if (!$classDefinition) {
                    $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);
                    continue;
                }

                $fieldsQuery = 'SELECT fieldname FROM ' . $tableName . ' GROUP BY fieldname';
                $fieldNames = $db->fetchCol($fieldsQuery);

                foreach ($fieldNames as $fieldName) {
                    $fieldDef = $classDefinition->getFieldDefinition($fieldName);
                    if (!$fieldDef && $isLocalized) {
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
