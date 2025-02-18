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

namespace Pimcore\Maintenance\Tasks\DataObject;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CleanupBrickTablesTaskHelper implements ConcreteTaskHelperInterface
{
    private const PIMCORE_OBJECTBRICK_CLASS_DIRECTORY = PIMCORE_CLASS_DEFINITION_DIRECTORY . '/objectbricks';

    public function __construct(
        private LoggerInterface $logger,
        private DataObjectTaskHelperInterface $helper,
        private Connection $db
    ) {
    }

    public function cleanupCollectionTable(): void
    {
        $collectionNames =
            $this->helper->getCollectionNames(self::PIMCORE_OBJECTBRICK_CLASS_DIRECTORY);

        if (empty($collectionNames)) {
            return;
        }

        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . $prefix . "%'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (str_starts_with($tableName, 'object_brick_localized_query_')) {
                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $idx = strpos($fieldDescriptor, '_');
                $brickType = substr($fieldDescriptor, 0, $idx);
                $brickType = $collectionNames[strtolower($brickType)] ?? $brickType;

                if (!$this->checkIfBrickExists($brickType, $tableName)) {
                    continue;
                }

                $classId = substr($fieldDescriptor, $idx + 1);
                $this->helper->cleanupTable($tableName, $classId);
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
