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
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Db\ConnectionInterface;

/**
 * @property ConnectionInterface $db
 */
trait CompositeIndexTrait
{
    /**
     * @internal
     *
     * @param string $table
     * @param string $type
     * @param array $compositeIndices
     *
     */
    public function updateCompositeIndices(string $table, string $type, array $compositeIndices)
    {
        // fetch existing indices
        $existingMap = [];
        // prefix with "c_"
        $existingIndicesRaw = $this->db->fetchAll('SHOW INDEXES FROM ' . $this->db->quoteIdentifier($table) . " WHERE Key_Name LIKE 'c\_%'");
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
            $this->db->query('ALTER TABLE `'.$table.'` DROP INDEX `'. $key.'`;');
        }

        foreach ($add as $key) {
            $columnName = $newIndicesMap[$key];
            $this->db->query(
                'ALTER TABLE `'.$table.'` ADD INDEX `' . $key.'` ('.$columnName.');'
            );
        }
    }
}
