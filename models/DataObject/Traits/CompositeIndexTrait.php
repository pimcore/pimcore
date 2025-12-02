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

namespace Pimcore\Model\DataObject\Traits;

use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @property Connection $db
 */
trait CompositeIndexTrait
{
    /**
     * @internal
     *
     *
     */
    public function updateCompositeIndices(string $table, string $type, array $compositeIndices): void
    {
        // fetch existing indices
        $existingMap = [];
        // prefix with "c_"
        $existingIndicesRaw = $this->db->fetchAllAssociative('SHOW INDEXES FROM ' . $this->db->quoteIdentifier($table) . " WHERE Key_Name LIKE 'c\_%'");
        foreach ($existingIndicesRaw as $item) {
            $key = $item['Key_name'];
            $column = $item['Column_name'];
            if (!array_key_exists($key, $existingMap)) {
                $existingMap[$key] = [];
            }
            $existingMap[$key][] = $column;
        }

        foreach ($existingMap as $key => $columns) {
            $existingMap[$key] = implode(',', $columns);
        }

        $newIndicesFilteredByType = array_filter($compositeIndices, function ($item) use ($type) {
            // query or localized_query
            return $item['index_type'] === $type;
        });

        $newIndicesMap = [];
        foreach ($newIndicesFilteredByType as $newIndex) {
            $key = $newIndex['index_key'];
            $columns = $newIndex['index_columns'];

            $newIndicesMap['c_' . $key] = implode(',', $columns);
        }

        $drop = [];
        $add = [];
        foreach ($existingMap as $key => $existing) {
            if (!isset($newIndicesMap[$key]) || $existing != $newIndicesMap[$key]) {
                $drop[] = $key;
            }
        }

        foreach ($newIndicesMap as $key => $new) {
            if (!isset($existingMap[$key]) || $existingMap[$key] != $new) {
                $add[] = $key;
            }
        }

        foreach ($drop as $key) {
            $this->db->executeQuery('ALTER TABLE `'.$table.'` DROP INDEX `'. $key.'`;');
        }

        foreach ($add as $key) {
            $columnName = $newIndicesMap[$key];
            $this->db->executeQuery(
                'ALTER TABLE `'.$table.'` ADD INDEX `' . $key.'` ('.$columnName.');'
            );
        }
    }
}
