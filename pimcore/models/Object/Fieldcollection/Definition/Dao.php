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
 * @package    Object\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Fieldcollection\Definition;

use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @property \Pimcore\Model\Object\Fieldcollection\Definition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use Object\ClassDefinition\Helper\Dao;

    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @param Object\ClassDefinition $class
     *
     * @return string
     */
    public function getTableName(Object\ClassDefinition $class)
    {
        return 'object_collection_' . $this->model->getKey() . '_' . $class->getId();
    }

    /**
     * @param Object\ClassDefinition $class
     *
     * @return string
     */
    public function getLocalizedTableName(Object\ClassDefinition $class)
    {
        return 'object_collection_' . $this->model->getKey() . '_localized_' . $class->getId();
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function delete(Object\ClassDefinition $class)
    {
        $table = $this->getTableName($class);
        $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function createUpdateTable(Object\ClassDefinition $class)
    {
        $table = $this->getTableName($class);

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $table . "` (
		  `o_id` int(11) NOT NULL default '0',
		  `index` int(11) default '0',
          `fieldname` varchar(190) default '',
          PRIMARY KEY (`o_id`,`index`,`fieldname`(190)),
          INDEX `o_id` (`o_id`),
          INDEX `index` (`index`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8mb4;");

        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;
        $protectedColums = ['o_id', 'index', 'fieldname'];

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, ([$table]));

        foreach ($this->model->getFieldDefinitions() as $value) {
            $key = $value->getName();

            if (is_array($value->getColumnType())) {
                // if a datafield requires more than one field
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($table, $key . '__' . $fkey, $fvalue, '', 'NULL');
                    $protectedColums[] = $key . '__' . $fkey;
                }
            } else {
                if ($value->getColumnType()) {
                    $this->addModifyColumn($table, $key, $value->getColumnType(), '', 'NULL');
                    $protectedColums[] = $key;
                }
            }
            $this->addIndexToField($value, $table, 'getColumnType', true, false, true);

            if ($value instanceof  Object\ClassDefinition\Data\Localizedfields) {
                $value->classSaved(
                    $class,
                    [
                        'context' => [
                            'containerType' => 'fieldcollection',
                            'containerKey' => $this->model->getKey()
                        ]
                    ]
                );
            }
        }

        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColums);
        $this->tableDefinitions = null;
    }
}
