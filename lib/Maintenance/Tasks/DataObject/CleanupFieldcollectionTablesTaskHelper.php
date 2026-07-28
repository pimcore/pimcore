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
class CleanupFieldcollectionTablesTaskHelper implements ConcreteTaskHelperInterface
{
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
        // empty when the last fieldcollection definition was removed - that is a valid state and we
        // still scan, so the now-orphaned tables get cleaned up.
        $collectionNames = $this->helper->getFieldcollectionCollectionNames();

        $prefix = 'object_collection_';
        $pattern = 'object\_collection\_%';
        $tableNames = $this->db->fetchAllAssociative("SHOW TABLES LIKE '" . $pattern . "'");

        foreach ($tableNames as $tableName) {
            $tableName = current($tableName);

            $fieldDescriptor = substr($tableName, strlen($prefix));

            // Underscores make the split between fieldcollection key and class id ambiguous, so
            // several live keys can claim this table. It is owned as soon as any candidate parse
            // resolves to a live class; only when no parse does is it an orphan to be dropped.
            $owned = false;
            foreach ($this->helper->matchCollectionKeys($fieldDescriptor, $collectionNames) as $fcType) {
                $classId = substr($fieldDescriptor, strlen($fcType) + 1);

                $isLocalized = false;

                if (str_starts_with($classId, 'localized_')) {
                    $isLocalized = true;
                    $classId = substr($classId, strlen('localized_'));
                }

                if ($this->helper->cleanupTable($tableName, $classId, $isLocalized)) {
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
