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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data\ObjectMetadata;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Data\ObjectMetadata $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use DataObject\ClassDefinition\Helper\Dao;

    /**
     * @var array|null
     */
    protected $tableDefinitions = null;

    /**
     * @param DataObject\Concrete $object
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     * @param string $type
     *
     * @throws \Exception
     */
    public function save(DataObject\Concrete $object, $ownertype, $ownername, $position, $index, $type = 'object')
    {
        $table = $this->getTablename($object);

        $dataTemplate = ['o_id' => $object->getId(),
            'dest_id' => $this->model->getElement()->getId(),
            'fieldname' => $this->model->getFieldname(),
            'ownertype' => $ownertype,
            'ownername' => $ownername ? $ownername : '',
            'index' => $index ? $index : '0',
            'position' => $position ? $position : '0',
            'type' => $type ? $type : 'object', ];

        foreach ($this->model->getColumns() as $column) {
            $getter = 'get' . ucfirst($column);
            $data = $dataTemplate;
            $data['column'] = $column;
            $data['data'] = $this->model->$getter();
            $this->db->insertOrUpdate($table, $data);
        }
    }

    /**
     * @param DataObject\Concrete $object
     *
     * @return string
     */
    protected function getTablename($object)
    {
        return 'object_metadata_' . $object->getClassId();
    }

    /**
     * @param DataObject\Concrete $source
     * @param int $destinationId
     * @param string $fieldname
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     *
     * @return null|DataObject\Data\ObjectMetadata
     */
    public function load(DataObject\Concrete $source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index)
    {
        $typeQuery = " AND (type = 'object' or type = '')";

        $query = 'SELECT * FROM ' . $this->getTablename($source) . ' WHERE o_id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? and `index` = ? ' . $typeQuery;
        $dataRaw = $this->db->fetchAll($query, [$source->getId(), $destinationId, $fieldname, $ownertype, $ownername, $position, $index]);
        if (!empty($dataRaw)) {
            $this->model->setObjectId($destinationId);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach ($dataRaw as $row) {
                if (in_array($row['column'], $columns)) {
                    $setter = 'set' . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        } else {
            return null;
        }
    }

    /**
     * @param DataObject\ClassDefinition $class
     */
    public function createOrUpdateTable(DataObject\ClassDefinition $class)
    {
        $classId = $class->getId();
        $table = 'object_metadata_' . $classId;

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $table . "` (
              `o_id` int(11) NOT NULL default '0',
              `dest_id` int(11) NOT NULL default '0',
	          `type` VARCHAR(50) NOT NULL DEFAULT '',
              `fieldname` varchar(71) NOT NULL,
              `column` varchar(190) NOT NULL,
              `data` text,
              `ownertype` ENUM('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
              `ownername` VARCHAR(70) NOT NULL DEFAULT '',
              `position` VARCHAR(70) NOT NULL DEFAULT '0',
              `index` int(11) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`o_id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`, `index`),
              INDEX `dest_id` (`dest_id`),
              INDEX `fieldname` (`fieldname`),
              INDEX `column` (`column`),
              INDEX `ownertype` (`ownertype`),
              INDEX `ownername` (`ownername`),
              INDEX `position` (`position`),
              INDEX `index` (`index`)
		) DEFAULT CHARSET=utf8mb4;");

        $this->handleEncryption($class, [$table]);
    }
}
