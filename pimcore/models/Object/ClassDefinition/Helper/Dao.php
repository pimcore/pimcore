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
 * @package    Object|Class
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Helper;

use Pimcore\Model\Object;

trait Dao
{
    /**
     * @param $field
     * @param $table
     * @param string $columnTypeGetter
     */
    protected function addIndexToField($field, $table, $columnTypeGetter = "getColumnType")
    {
        $columnType = $field->$columnTypeGetter();

        if ($field->getIndex()) {
            if (is_array($columnType)) {
                // multicolumn field
                foreach ($columnType as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    $this->db->queryIgnoreError("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                }
            } else {
                // single -column field
                $columnName = $field->getName();
                $this->db->queryIgnoreError("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
            }
        } else {
            if (is_array($columnType)) {
                // multicolumn field
                foreach ($columnType as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    $this->db->queryIgnoreError("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                }
            } else {
                // single -column field
                $columnName = $field->getName();
                $this->db->queryIgnoreError("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
            }
        }
    }

    /**
     * @param $table
     * @param $colName
     * @param $type
     * @param $default
     * @param $null
     */
    protected function addModifyColumn($table, $colName, $type, $default, $null)
    {
        $existingColumns = $this->getValidTableColumns($table, false);

        $existingColName = null;

        // check for existing column case insensitive eg a rename from myInput to myinput
        $matchingExisting = preg_grep('/^' . preg_quote($colName, '/') . '$/i', $existingColumns);
        if (is_array($matchingExisting) && !empty($matchingExisting)) {
            $existingColName = current($matchingExisting);
        }
        if ($existingColName === null) {
            $this->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
            $this->resetValidTableColumnsCache($table);
        } else {
            if (!Object\ClassDefinition\Service::skipColumn($this->tableDefinitions, $table, $colName, $type, $default, $null)) {
                $this->db->query('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
            }
        }
    }

    /**
     * @param $table
     * @param $columnsToRemove
     * @param $protectedColumns
     */
    protected function removeUnusedColumns($table, $columnsToRemove, $protectedColumns)
    {
        if (is_array($columnsToRemove) && count($columnsToRemove) > 0) {
            foreach ($columnsToRemove as $value) {
                //if (!in_array($value, $protectedColumns)) {
                if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP COLUMN `' . $value . '`;');
                }
            }
        }
    }
}
