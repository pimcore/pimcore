<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Fieldcollection\Definition;

use Pimcore\Model;
use Pimcore\Model\Object;

class Dao extends Model\Dao\AbstractDao
{
    use Object\ClassDefinition\Helper\Dao;

    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @param Object\ClassDefinition $class
     * @return string
     */
    public function getTableName(Object\ClassDefinition $class)
    {
        return "object_collection_" . $this->model->getKey() . "_" . $class->getId();
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function delete(Object\ClassDefinition $class)
    {
        $table = $this->getTableName($class);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function createUpdateTable(Object\ClassDefinition $class)
    {
        $table = $this->getTableName($class);

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
		  `o_id` int(11) NOT NULL default '0',
		  `index` int(11) default '0',
          `fieldname` varchar(255) default NULL,
          PRIMARY KEY (`o_id`,`index`,`fieldname`(255)),
          INDEX `o_id` (`o_id`),
          INDEX `index` (`index`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8;");

        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;
        $protectedColums = array("o_id", "index","fieldname");

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, (array($table)));

        foreach ($this->model->getFieldDefinitions() as $value) {
            $key = $value->getName();



            if (is_array($value->getColumnType())) {
                // if a datafield requires more than one field
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($table, $key . "__" . $fkey, $fvalue, "", "NULL");
                    $protectedColums[] = $key . "__" . $fkey;
                }
            } else {
                if ($value->getColumnType()) {
                    $this->addModifyColumn($table, $key, $value->getColumnType(), "", "NULL");
                    $protectedColums[] = $key;
                }
            }
            $this->addIndexToField($value, $table);

            if ($value instanceof  Object\ClassDefinition\Data\Localizedfields) {
                $value->classSaved($class,
                    array(
                        "context" => array(
                            "containerType" => "fieldcollection",
                            "containerKey" => $this->model->getKey()
                        )
                    ));
            }
        }

        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColums);
        $this->tableDefinitions = null;
    }
}
