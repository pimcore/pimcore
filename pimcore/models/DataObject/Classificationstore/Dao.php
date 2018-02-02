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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Classificationstore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @return string
     */
    public function getDataTableName()
    {
        return 'object_classificationstore_data_' . $this->model->getClass()->getId();
    }

    /**
     * @return string
     */
    public function getGroupsTableName()
    {
        return 'object_classificationstore_groups_' . $this->model->getClass()->getId();
    }

    public function save()
    {
        $object = $this->model->object;
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $fieldname = $this->model->getFieldname();

        $this->db->delete($dataTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);

        $items = $this->model->getItems();

        $collectionMapping = $this->model->getGroupCollectionMappings();

        foreach ($items as $groupId => $group) {
            foreach ($group as $keyId => $keyData) {
                $keyConfig = DefinitionCache::get($keyId);
                $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);

                foreach ($keyData as $language => $value) {
                    $collectionId = $collectionMapping[$groupId];
                    $data = [
                        'o_id' => $objectId,
                        'collectionId' => $collectionId,
                        'groupId' => $groupId,
                        'keyId' => $keyId,
                        'fieldname' => $fieldname,
                        'language' => $language,
                        'type' => $keyConfig->getType()
                    ];

                    if ($fd instanceof DataObject\ClassDefinition\Data\Password) {
                        $value = $fd->getDataForResource($value, null, []);
                        $this->model->setLocalizedKeyValue($groupId, $keyId, $value, $language);
                    } else {
                        $value = $fd->getDataForResource($value, $this->model->object);
                    }
                    $value = $fd->marshal($value, $object);

                    $data['value'] = $value['value'];
                    $data['value2'] = $value['value2'];

                    $this->db->insertOrUpdate($dataTable, $data);
                }
            }
        }

        $groupsTable = $this->getGroupsTableName();

        $this->db->delete($groupsTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);

        $activeGroups = $this->model->getActiveGroups();
        if (is_array($activeGroups)) {
            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $data = [
                        'o_id' => $objectId,
                        'groupId' => $activeGroupId,
                        'fieldname' => $fieldname
                    ];
                    $this->db->insertOrUpdate($groupsTable, $data);
                }
            }
        }
    }

    public function delete()
    {
        $object = $this->model->object;
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $groupsTable = $this->getGroupsTableName();

        // remove relations
        $this->db->delete($dataTable, ['o_id' => $objectId]);
        $this->db->delete($groupsTable, ['o_id' => $objectId]);
    }

    public function load()
    {
        /** @var $classificationStore DataObject\Classificationstore */
        $classificationStore = $this->model;
        $object = $this->model->getObject();
        $dataTableName = $this->getDataTableName();
        $objectId = $object->getId();
        $fieldname = $this->model->getFieldname();

        $query = 'SELECT * FROM ' . $dataTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);

        $groupCollectionMapping = [];

        foreach ($data as $item) {
            $groupId = $item['groupId'];
            $keyId = $item['keyId'];
            $collectionId = $item['collectionId'];
            $groupCollectionMapping[$groupId] = $collectionId;

            $value = [
                'value' => $item['value'],
                'value2' => $item['value2']
            ];

            $keyConfig = DefinitionCache::get($keyId);
            if (!$keyConfig) {
                Logger::error('Could not resolve key with ID: ' . $keyId);
                continue;
            }

            $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $value = $fd->unmarshal($value, $object);

            $value = $fd->getDataFromResource($value, $object);

            $language = $item['language'];
            $classificationStore->setLocalizedKeyValue($groupId, $keyId, $value, $language);
        }

        $groupsTableName = $this->getGroupsTableName();

        $query = 'SELECT * FROM ' . $groupsTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);
        $list = [];

        foreach ($data as $item) {
            $list[$item['groupId']] = true;
        }

        $classificationStore->setActiveGroups($list);
        $classificationStore->setGroupCollectionMappings($groupCollectionMapping);
    }

    public function createUpdateTable()
    {
        $groupsTable = $this->getGroupsTableName();
        $dataTable = $this->getDataTableName();

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $groupsTable . '` (
            `o_id` BIGINT(20) NOT NULL,
            `groupId` BIGINT(20) NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            PRIMARY KEY (`groupId`, `o_id`, `fieldname`),
            INDEX `o_id` (`o_id`),
            INDEX `fieldname` (`fieldname`)
        ) DEFAULT CHARSET=utf8mb4;');

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $dataTable . '` (
            `o_id` BIGINT(20) NOT NULL,
            `collectionId` BIGINT(20) NULL,
            `groupId` BIGINT(20) NOT NULL,
            `keyId` BIGINT(20) NOT NULL,
            `value` LONGTEXT NULL,
	        `value2` LONGTEXT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            `language` VARCHAR(10) NOT NULL,
            `type` VARCHAR(50) NULL,
            PRIMARY KEY (`groupId`, `keyId`, `o_id`, `fieldname`, `language`),
            INDEX `o_id` (`o_id`),
            INDEX `groupId` (`groupId`),
            INDEX `keyId` (`keyId`),
            INDEX `fieldname` (`fieldname`),
            INDEX `language` (`language`)
        ) DEFAULT CHARSET=utf8mb4;');

        $this->tableDefinitions = null;
    }
}
