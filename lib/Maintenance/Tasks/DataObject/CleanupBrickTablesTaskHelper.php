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

namespace Pimcore\Maintenance\Tasks\DataObject;

use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class CleanupBrickTablesTaskHelper implements ConcreteTaskHelperInterface
{
    private const PIMCORE_OBJECTBRICK_CLASS_DIRECTORY = PIMCORE_CLASS_DEFINITION_DIRECTORY . '/objectbricks';

    public function __construct(
        private DataObjectTaskHelperInterface $helper,
        private Connection $db
    ) {
    }

    public function cleanupCollectionTable(): void
    {
        // If the definition directory itself is unavailable (e.g. a broken/incomplete deployment)
        // we must not treat any table as orphaned - otherwise live tables would be dropped.
        if (!is_dir(self::PIMCORE_OBJECTBRICK_CLASS_DIRECTORY)) {
            return;
        }

        // May be empty when the last brick definition was removed - that is a valid state and we
        // still scan, so the now-orphaned tables get cleaned up.
        $collectionNames = $this->helper->getCollectionNames(self::PIMCORE_OBJECTBRICK_CLASS_DIRECTORY);

        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            // Escape the LIKE wildcards in the literal prefix so only the intended tables match.
            $pattern = str_replace('_', '\_', $prefix) . '%';
            $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . $pattern . "'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (str_starts_with($tableName, 'object_brick_localized_query_')) {
                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));
                $brickType = $this->helper->matchCollectionKey($fieldDescriptor, $collectionNames);

                if ($brickType === null) {
                    // No existing brick definition owns this table -> orphan, drop it.
                    $this->helper->dropOrphanedTable($tableName);

                    continue;
                }

                $classId = substr($fieldDescriptor, strlen($brickType) + 1);
                $this->helper->cleanupTable($tableName, $classId);
            }
        }
    }
}
