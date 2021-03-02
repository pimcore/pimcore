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
    use DataObject\ClassDefinition\Helper\Dao;

    /**
     * @var array|null
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

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields()) {
            return;
        }
        $object = $this->model->getObject();
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $fieldname = $this->model->getFieldname();

        $this->db->delete($dataTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);

        $items = $this->model->getItems();
        $activeGroups = $this->model->getActiveGroups();

        $collectionMapping = $this->model->getGroupCollectionMappings();

        foreach ($items as $groupId => $group) {
            foreach ($group as $keyId => $keyData) {
                if (!isset($activeGroups[$groupId])) {
                    continue;
                }
                $keyConfig = DefinitionCache::get($keyId);
                $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);

                foreach ($keyData as $language => $value) {
                    $collectionId = $collectionMapping[$groupId] ?? null;
                    $data = [
                        'o_id' => $objectId,
                        'collectionId' => $collectionId,
                        'groupId' => $groupId,
                        'keyId' => $keyId,
                        'fieldname' => $fieldname,
                        'language' => $language,
                        'type' => $keyConfig->getType(),
                    ];

                    if ($fd instanceof DataObject\ClassDefinition\Data\Password) {
                        $value = $fd->getDataForResource($value, null, []);
                        $this->model->setLocalizedKeyValue($groupId, $keyId, $value, $language);
                    } elseif ($fd instanceof DataObject\ClassDefinition\Data\EncryptedField) {
                        $value = $fd->getDataForResource($value, $object, ['skipEncryption' => true]);
                        $delegate = $fd->getDelegate();
                        $value = new DataObject\Data\EncryptedField($delegate, $value);
                    } elseif ($fd instanceof DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
                        $value = $fd->getDataForResource($value, $this->model->getObject());
                    }
                    $value = $fd->marshal($value, $object);

                    $data['value'] = $value['value'] ?? null;
                    $data['value2'] = $value['value2'] ?? null;

                    $this->db->insertOrUpdate($dataTable, $data);
                }
            }
        }

        $groupsTable = $this->getGroupsTableName();

        $this->db->delete($groupsTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);

        if (is_array($activeGroups)) {
            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $data = [
                        'o_id' => $objectId,
                        'groupId' => $activeGroupId,
                        'fieldname' => $fieldname,
                    ];
                    $this->db->insertOrUpdate($groupsTable, $data);
                }
            }
        }
    }

    public function delete()
    {
        $object = $this->model->getObject();
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $groupsTable = $this->getGroupsTableName();

        // remove relations
        $this->db->delete($dataTable, ['o_id' => $objectId]);
        $this->db->delete($groupsTable, ['o_id' => $objectId]);
    }

    /**
     * @throws \Exception
     */
    public function load()
    {
        /** @var DataObject\Classificationstore $classificationStore */
        $classificationStore = $this->model;
        $object = $this->model->getObject();
        $dataTableName = $this->getDataTableName();
        $objectId = $object->getId();
        $fieldname = $this->model->getFieldname();
        $groupsTableName = $this->getGroupsTableName();

        $query = 'SELECT * FROM ' . $groupsTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);
        $list = [];

        foreach ($data as $item) {
            $list[$item['groupId']] = true;
        }

        $query = 'SELECT * FROM ' . $dataTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAll($query);

        $groupCollectionMapping = [];

        foreach ($data as $item) {
            if (!isset($list[$item['groupId']])) {
                continue;
            }

            $groupId = $item['groupId'];
            $keyId = $item['keyId'];
            $collectionId = $item['collectionId'];
            $groupCollectionMapping[$groupId] = $collectionId;

            $value = [
                'value' => $item['value'],
                'value2' => $item['value2'],
            ];

            $keyConfig = DefinitionCache::get($keyId);
            if (!$keyConfig) {
                Logger::error('Could not resolve key with ID: ' . $keyId);
                continue;
            }

            $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $value = $fd->unmarshal($value, $object);

            if ($fd instanceof DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
                $value = $fd->getDataFromResource($value, $object, ['skipDecryption' => true]);
            }

            $language = $item['language'];
            $classificationStore->setLocalizedKeyValue($groupId, $keyId, $value, $language);
        }

        $classificationStore->setActiveGroups($list);
        $classificationStore->setGroupCollectionMappings($groupCollectionMapping);
        $classificationStore->resetDirtyMap();
    }

    public function createUpdateTable()
    {
        $groupsTable = $this->getGroupsTableName();
        $dataTable = $this->getDataTableName();

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $groupsTable . '` (
            `o_id` BIGINT(20) NOT NULL,
            `groupId` BIGINT(20) NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            PRIMARY KEY (`o_id`, `fieldname`, `groupId`)
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
            PRIMARY KEY (`o_id`, `fieldname`, `groupId`, `keyId`, `language`),
            INDEX `keyId` (`keyId`),
            INDEX `language` (`language`)
        ) DEFAULT CHARSET=utf8mb4;');

        $this->tableDefinitions = null;

        $this->handleEncryption($this->model->getClass(), [$groupsTable, $dataTable]);
    }
}
