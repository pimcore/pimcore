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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Db\Helper;
use Pimcore\Element\MarshallerService;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 *
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
        if (!DataObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields()) {
            return;
        }
        $object = $this->model->getObject();
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $fieldname = $this->model->getFieldname();

        $dataExists = $this->db->fetchOne('SELECT `o_id` FROM `'.$dataTable."` WHERE
         `o_id` = '".$objectId."' AND `fieldname` = '".$fieldname."' LIMIT 1");
        if ($dataExists) {
            $this->db->delete($dataTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);
        }

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

                    $encodedData = [];

                    if ($fd instanceof NormalizerInterface) {
                        $normalizedData = $fd->normalize($value, [
                            'object' => $object,
                            'fieldDefinition' => $fd,
                        ]);

                        /** @var MarshallerService $marshallerService */
                        $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                        if ($marshallerService->supportsFielddefinition('classificationstore', $fd->getFieldtype())) {
                            $marshaller = $marshallerService->buildFieldefinitionMarshaller('classificationstore', $fd->getFieldtype());
                            // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                            $encodedData = $marshaller->marshal($normalizedData, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'classificationstore']);
                        } else {
                            $encodedData['value'] = $normalizedData;
                        }
                    }

                    $data['value'] = $encodedData['value'] ?? null;
                    $data['value2'] = $encodedData['value2'] ?? null;

                    Helper::insertOrUpdate($this->db, $dataTable, $data);
                }
            }
        }

        $groupsTable = $this->getGroupsTableName();

        $dataExists = $this->db->fetchOne('SELECT `o_id` FROM `'.$groupsTable."` WHERE
         `o_id` = '".$objectId."' AND `fieldname` = '".$fieldname."' LIMIT 1");
        if ($dataExists) {
            $this->db->delete($groupsTable, ['o_id' => $objectId, 'fieldname' => $fieldname]);
        }

        if (is_array($activeGroups)) {
            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $data = [
                        'o_id' => $objectId,
                        'groupId' => $activeGroupId,
                        'fieldname' => $fieldname,
                    ];
                    Helper::insertOrUpdate($this->db, $groupsTable, $data);
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
        $classificationStore = $this->model;
        $object = $this->model->getObject();
        $dataTableName = $this->getDataTableName();
        $objectId = $object->getId();
        $fieldname = $this->model->getFieldname();
        $groupsTableName = $this->getGroupsTableName();

        $query = 'SELECT * FROM ' . $groupsTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAllAssociative($query);
        $list = [];

        foreach ($data as $item) {
            $list[$item['groupId']] = true;
        }

        $query = 'SELECT * FROM ' . $dataTableName . ' WHERE o_id = ' . $this->db->quote($objectId) . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAllAssociative($query);

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

            if ($fd instanceof NormalizerInterface) {
                /** @var MarshallerService $marshallerService */
                $marshallerService = \Pimcore::getContainer()->get(MarshallerService::class);

                if ($marshallerService->supportsFielddefinition('classificationstore', $fd->getFieldtype())) {
                    $unmarshaller = $marshallerService->buildFieldefinitionMarshaller('classificationstore', $fd->getFieldtype());
                    // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                    $value = $unmarshaller->unmarshal($value, ['object' => $object, 'fieldDefinition' => $fd, 'format' => 'classificationstore']);
                } else {
                    $value = $value['value'];
                }

                $value = $fd->denormalize($value, [
                    'object' => $object,
                    'fieldDefinition' => $fd,
                ]);
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

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $groupsTable . '` (
            `o_id` INT(11) UNSIGNED NOT NULL,
            `groupId` BIGINT(20) NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            PRIMARY KEY (`o_id`, `fieldname`, `groupId`),
            CONSTRAINT `'.self::getForeignKeyName($groupsTable, 'o_id').'` FOREIGN KEY (`o_id`) REFERENCES objects (`o_id`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4;');

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $dataTable . '` (
            `o_id` INT(11) UNSIGNED NOT NULL,
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
            INDEX `language` (`language`),
            CONSTRAINT `'.self::getForeignKeyName($dataTable, 'o_id').'` FOREIGN KEY (`o_id`) REFERENCES objects (`o_id`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4;');

        $this->tableDefinitions = null;

        $this->handleEncryption($this->model->getClass(), [$groupsTable, $dataTable]);
    }
}
