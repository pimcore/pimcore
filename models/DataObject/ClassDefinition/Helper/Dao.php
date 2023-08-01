<?php

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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Db\Helper;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
trait Dao
{
    protected function addIndexToField(DataObject\ClassDefinition\Data $field, string $table, string $columnTypeGetter = 'getColumnType', bool $considerUniqueIndex = false, bool $isLocalized = false, bool $isFieldcollection = false): void
    {
        $columnType = $field->$columnTypeGetter();

        $prefixes = [
            'p_index_' => ['enabled' => !$considerUniqueIndex && $field->getIndex(), 'unique' => false],
            'u_index_' => ['enabled' => $considerUniqueIndex && $field->getUnique(), 'unique' => true],

        ];

        foreach ($prefixes as $prefix => $config) {
            $enabled = $config['enabled'];
            $unique = $config['unique'];
            $uniqueStr = $unique ? ' UNIQUE ' : '';

            if ($enabled) {
                if (is_array($columnType)) {
                    // multicolumn field
                    foreach ($columnType as $fkey => $fvalue) {
                        $indexName = $field->getName().'__'.$fkey;
                        $columnName = '`' . $indexName . '`';
                        if ($unique) {
                            if ($isLocalized) {
                                $columnName .= ',`language`';
                            } elseif ($isFieldcollection) {
                                $columnName .= ',`fieldname`';
                            }
                        }
                        Helper::queryIgnoreError($this->db, 'ALTER TABLE `'.$table.'` ADD ' . $uniqueStr . 'INDEX `' . $prefix . $indexName.'` ('.$columnName.');',
                            [UniqueConstraintViolationException::class]
                        );
                    }
                } else {
                    // single -column field
                    $indexName = $field->getName();
                    $columnName = '`' . $indexName . '`';
                    if ($unique) {
                        if ($isLocalized) {
                            $columnName .= ',`language`';
                        } elseif ($isFieldcollection) {
                            $columnName .= ',`fieldname`';
                        }
                    }
                    Helper::queryIgnoreError($this->db, 'ALTER TABLE `'.$table.'` ADD ' . $uniqueStr . 'INDEX `' . $prefix . $indexName.'` ('.$columnName.');',
                        [UniqueConstraintViolationException::class]
                    );
                }
            } else {
                if (is_array($columnType)) {
                    // multicolumn field
                    foreach ($columnType as $fkey => $fvalue) {
                        $columnName = $field->getName().'__'.$fkey;
                        Helper::queryIgnoreError($this->db, 'ALTER TABLE `'.$table.'` DROP INDEX `'. $prefix . $columnName.'`;');
                    }
                } else {
                    // single -column field
                    $columnName = $field->getName();
                    Helper::queryIgnoreError($this->db, 'ALTER TABLE `'.$table.'` DROP INDEX `'. $prefix . $columnName.'`;');
                }
            }
        }
    }

    protected function addModifyColumn(string $table, string $colName, string $type, string $default, string $null): void
    {
        $existingColumns = $this->getValidTableColumns($table, false);

        $existingColName = null;

        // check for existing column case insensitive eg a rename from myInput to myinput
        $matchingExisting = preg_grep('/^' . preg_quote($colName, '/') . '$/i', $existingColumns);
        if (is_array($matchingExisting) && !empty($matchingExisting)) {
            $existingColName = current($matchingExisting);
        }
        if ($existingColName === null) {
            $this->db->executeQuery('ALTER TABLE `' . $table . '` ADD COLUMN `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
            $this->resetValidTableColumnsCache($table);
        } else {
            if (!DataObject\ClassDefinition\Service::skipColumn($this->tableDefinitions, $table, $colName, $type, $default, $null)) {
                $this->db->executeQuery('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
            }
        }
    }

    /**
     * @param string[] $columnsToRemove
     * @param string[] $protectedColumns
     */
    protected function removeUnusedColumns(string $table, array $columnsToRemove, array $protectedColumns): void
    {
        $dropColumns = [];
        foreach ($columnsToRemove as $value) {
            //if (!in_array($value, $protectedColumns)) {
            if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
                $dropColumns[] = 'DROP COLUMN `' . $value . '`';
            }
        }
        if ($dropColumns) {
            $this->db->executeQuery('ALTER TABLE `' . $table . '` ' . implode(', ', $dropColumns) . ';');
            $this->resetValidTableColumnsCache($table);
        }
    }

    /**
     * @param string[] $tables
     */
    protected function handleEncryption(DataObject\ClassDefinition $classDefinition, array $tables): void
    {
        if ($classDefinition->getEncryption()) {
            $this->encryptTables($tables);
            $classDefinition->addEncryptedTables($tables);
        } elseif ($classDefinition->hasEncryptedTables()) {
            $this->decryptTables($classDefinition, $tables);
            $classDefinition->removeEncryptedTables($tables);
        }
    }

    /**
     * @param string[] $tables
     */
    protected function encryptTables(array $tables): void
    {
        foreach ($tables as $table) {
            $this->db->executeQuery('ALTER TABLE ' . $this->db->quoteIdentifier($table) . ' ENCRYPTED=YES;');
        }
    }

    /**
     * @param string[] $tables
     */
    protected function decryptTables(DataObject\ClassDefinition $classDefinition, array $tables): void
    {
        foreach ($tables as $table) {
            if ($classDefinition->isEncryptedTable($table)) {
                $this->db->executeQuery('ALTER TABLE ' . $this->db->quoteIdentifier($table) . ' ENCRYPTED=NO;');
            }
        }
    }

    /**
     * @param string[] $columnsToRemove
     * @param string[] $protectedColumns
     */
    protected function removeIndices(string $table, array $columnsToRemove, array $protectedColumns): void
    {
        if ($columnsToRemove) {
            $lowerCaseColumns = array_map('strtolower', $protectedColumns);
            foreach ($columnsToRemove as $value) {
                if (!in_array(strtolower($value), $lowerCaseColumns)) {
                    Helper::queryIgnoreError($this->db, 'ALTER TABLE `'.$table.'` DROP INDEX `u_index_'. $value . '`;');
                }
            }
            $this->resetValidTableColumnsCache($table);
        }
    }
}
