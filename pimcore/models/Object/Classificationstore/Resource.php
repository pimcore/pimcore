<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @return string
     */
    public function getDataTableName () {
        return "object_classificationstore_data_" . $this->model->getClass()->getId();
    }

    /**
     * @return string
     */
    public function getGroupsTableName () {
        return "object_classificationstore_groups_" . $this->model->getClass()->getId();
    }


    /**
     *
     */
    public function save () {
        $object = $this->model->object;
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $fieldname = $this->model->getFieldname();

        $condition = $this->db->quoteInto("o_id = ?", $objectId)
                        . " AND " . $this->db->quoteInto("fieldname = ?", $fieldname);
        $this->db->delete($dataTable, $condition);

        $items = $this->model->getItems();

        $collectionMapping = $this->model->getGroupCollectionMappings();


        foreach ($items as $groupId => $group) {
            foreach ($group as $keyId => $keyData) {
                $keyConfig = DefinitionCache::get($keyId);
                $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);

                $values = array();
                foreach ($keyData as $language => $value) {
                    $value = $fd->getDataForResource($value, $this->model->object);
                    $value = $fd->marshal($value, $object);
                    $collectionId = $collectionMapping[$groupId];

                    $data = array(
                        "o_id" => $objectId,
                        "collectionId" => $collectionId,
                        "groupId" => $groupId,
                        "keyId" => $keyId,
                        "value" => $value,
                        "fieldname" => $fieldname,
                        "language" => $language

                    );
                    $this->db->insertOrUpdate($dataTable, $data);

                }
            }
        }


        $groupsTable = $this->getGroupsTableName();

        $condition = $this->db->quoteInto("o_id = ?", $objectId)
            . " AND " . $this->db->quoteInto("fieldname = ?", $fieldname);
        $this->db->delete($groupsTable, $condition);

        $activeGroups = $this->model->getActiveGroups();
        if (is_array($activeGroups)) {
            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $data = array(
                        "o_id" => $objectId,
                        "groupId" => $activeGroupId,
                        "fieldname" => $fieldname
                    );
                    $this->db->insertOrUpdate($groupsTable, $data);
                }
            }
        }
    }

    /**
     *
     */
    public function delete () {
        $object = $this->model->object;
        $objectId = $object->getId();
        $classId = $object->getClassId();
        $dataTable = $this->getDataTableName();
        $groupsTable = $this->getGroupsTableName();

        $condition = $this->db->quoteInto("o_id = ?", $objectId);

        // remove relations
        $this->db->delete($dataTable, $condition);
        $this->db->delete($groupsTable, $condition);
    }

    /**
     *
     */
    public function load () {
        /** @var  $classificationStore Object\Classificationstore */
        $classificationStore = $this->model;
        $object = $this->model->getObject();
        $dataTableName = $this->getDataTableName();
        $objectId = $object->getId();
        $fieldname = $this->model->getFieldname();

        $query = "SELECT * FROM " . $dataTableName . " WHERE o_id = " . $this->db->quote($objectId) . " AND fieldname = " . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);

        $groupCollectionMapping = array();

        foreach ($data as $item) {
            $groupId = $item["groupId"];
            $keyId = $item["keyId"];
            $collectionId = $item["collectionId"];
            $groupCollectionMapping[$groupId] = $collectionId;

            $value = $item["value"];

            $keyConfig = DefinitionCache::get($keyId);
            $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $value = $fd->unmarshal($value, $object);

            $value = $fd->getDataFromResource($value);


            $language = $item["language"];
            $classificationStore->setLocalizedKeyValue($groupId, $keyId, $value, $language);
        }

        $groupsTableName = $this->getGroupsTableName();

        $query = "SELECT * FROM " . $groupsTableName . " WHERE o_id = " . $this->db->quote($objectId) . " AND fieldname = " . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);
        $list = array();

        foreach ($data as $item) {
            $list[$item["groupId"]] = true;
        }

        $classificationStore->setActiveGroups($list);
        $classificationStore->setGroupCollectionMappings($groupCollectionMapping);
    }




    /**
     *
     */
    public function createUpdateTable () {
        $groupsTable = $this->getGroupsTableName();
        $dataTable = $this->getDataTableName();

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $groupsTable . "` (
            `o_id` BIGINT(20) NOT NULL,
            `groupId` BIGINT(20) NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            PRIMARY KEY (`groupId`, `o_id`, `fieldname`)
        ) DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $dataTable . "` (
            `o_id` BIGINT(20) NOT NULL,
            `collectionId` BIGINT(20) NULL,
            `groupId` BIGINT(20) NOT NULL,
            `keyId` BIGINT(20) NOT NULL,
            `value` LONGTEXT NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            `language` VARCHAR(10) NOT NULL,
            PRIMARY KEY (`groupId`, `keyId`, `o_id`, `fieldname`, `language`)
        ) DEFAULT CHARSET=utf8;");


//
        $this->tableDefinitions = null;
    }


}
