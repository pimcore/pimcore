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
    private const LOCALIZED_QUERY_PREFIX = 'object_brick_localized_query_';

    public function __construct(
        private DataObjectTaskHelperInterface $helper,
        private Connection $db
    ) {
    }

    public function cleanupCollectionTable(): void
    {
        // If the class definition store itself is unavailable (e.g. a broken/incomplete deployment)
        // we must not treat any table as orphaned - otherwise live tables would be dropped.
        if (!is_dir(PIMCORE_CLASS_DEFINITION_DIRECTORY)) {
            return;
        }

        // Authoritative ownership list, loaded from all supported definition directories. May be
        // empty when the last brick definition was removed - that is a valid state and we still
        // scan, so the now-orphaned tables get cleaned up.
        $collectionNames = $this->helper->getObjectBrickCollectionNames();

        $tableTypes = ['store', 'query', 'localized'];
        foreach ($tableTypes as $tableType) {
            $prefix = 'object_brick_' . $tableType . '_';
            // Escape the LIKE wildcards in the literal prefix so only the intended tables match.
            $pattern = str_replace('_', '\_', $prefix) . '%';
            $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . $pattern . "'");

            foreach ($tableNames as $tableName) {
                $tableName = current($tableName);

                if (str_starts_with($tableName, self::LOCALIZED_QUERY_PREFIX)) {
                    // Localized query tables carry a trailing language suffix and are not cleaned
                    // field-by-field here, but an orphaned one (its brick definition is gone) must
                    // still be dropped instead of skipped.
                    $localizedQueryDescriptor = substr($tableName, strlen(self::LOCALIZED_QUERY_PREFIX));
                    if ($this->helper->matchCollectionKeys($localizedQueryDescriptor, $collectionNames) === []) {
                        $this->helper->dropOrphanedTable($tableName);
                    }

                    continue;
                }

                $fieldDescriptor = substr($tableName, strlen($prefix));

                // Underscores make the split between brick key and class id ambiguous, so several
                // live keys can claim this table. It is owned as soon as any candidate parse
                // resolves to a live class; only when no parse does is it an orphan to be dropped.
                $owned = false;
                foreach ($this->helper->matchCollectionKeys($fieldDescriptor, $collectionNames) as $brickType) {
                    $classId = substr($fieldDescriptor, strlen($brickType) + 1);
                    if ($this->helper->cleanupTable($tableName, $classId)) {
                        $owned = true;

                        break;
                    }
                }

                if (!$owned) {
                    $this->helper->dropOrphanedTable($tableName);
                }
            }
        }
    }
}
