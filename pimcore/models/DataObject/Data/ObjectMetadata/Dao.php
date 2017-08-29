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
    /**
     * @param DataObject\Concrete $object
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $type
     *
     * @throws \Exception
     */
    public function save(DataObject\Concrete $object, $ownertype, $ownername, $position, $type = 'object')
    {
        $table = $this->getTablename($object);

        $dataTemplate = ['o_id' => $object->getId(),
            'dest_id' => $this->model->getElement()->getId(),
            'fieldname' => $this->model->getFieldname(),
            'ownertype' => $ownertype,
            'ownername' => $ownername ? $ownername : '',
            'position' => $position ? $position : '0',
            'type' => $type ? $type : 'object'];

        foreach ($this->model->getColumns() as $column) {
            $getter = 'get' . ucfirst($column);
            $data = $dataTemplate;
            $data['column'] = $column;
            $data['data'] = $this->model->$getter();
            $this->db->insert($table, $data);
        }
    }

    /**
     * @param $object
     *
     * @return string
     */
    protected function getTablename($object)
    {
        return 'object_metadata_' . $object->getClassId();
    }

    /**
     * @param DataObject\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $type
     *
     * @return null|Model\Dao\\Pimcore\Model\DataObject\AbstractObject
     */
    public function load(DataObject\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position, $type = 'object')
    {
        if ($type == 'object') {
            $typeQuery = " AND (type = 'object' or type = '')";
        } else {
            $typeQuery = ' AND type = ' . $this->db->quote($type);
        }

        $dataRaw = $this->db->fetchAll('SELECT * FROM ' . $this->getTablename($source) . ' WHERE o_id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? ' . $typeQuery, [$source->getId(), $destination->getId(), $fieldname, $ownertype, $ownername, $position]);
        if (!empty($dataRaw)) {
            $this->model->setElement($destination);
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
     * @param $class
     */
    public function createOrUpdateTable($class)
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
              PRIMARY KEY (`o_id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`),
              INDEX `o_id` (`o_id`),
              INDEX `dest_id` (`dest_id`),
              INDEX `fieldname` (`fieldname`),
              INDEX `column` (`column`),
              INDEX `ownertype` (`ownertype`),
              INDEX `ownername` (`ownername`),
              INDEX `position` (`position`)
		) DEFAULT CHARSET=utf8mb4;");
    }
}
