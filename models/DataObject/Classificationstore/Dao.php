<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Exception;
use Pimcore;
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
     * Rows per multi-row statement, so that a store with many keys and languages does not build a
     * statement of unbounded length. What the rows weigh is bounded separately,
     * by {@see self::getWriteBatchByteBudget()}.
     */
    private const WRITE_BATCH_SIZE = 200;

    /**
     * The payload a single multi-row statement may carry when the server does not answer what it
     * accepts.
     */
    private const WRITE_BATCH_FALLBACK_BYTES = 1048576;

    private const DATA_COLUMNS = [
        'id', 'collectionId', 'groupId', 'keyId', 'fieldname', 'language', 'type', 'value', 'value2',
    ];

    /**
     * The columns of a data row that the diff compares: everything the save writes that is not
     * part of the primary key.
     */
    private const DATA_VALUE_COLUMNS = ['collectionId', 'type', 'value', 'value2'];

    private const GROUP_COLUMNS = ['id', 'groupId', 'fieldname'];

    /**
     * @see self::getWriteBatchByteBudget()
     */
    private static ?int $writeBatchByteBudget = null;

    protected array $tableDefinitions = [];

    public function getDataTableName(): string
    {
        return 'object_classificationstore_data_' . $this->model->getClass()->getId();
    }

    public function getGroupsTableName(): string
    {
        return 'object_classificationstore_groups_' . $this->model->getClass()->getId();
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!DataObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields()) {
            return;
        }
        $object = $this->model->getObject();
        $objectId = $object->getId();
        $fieldname = $this->model->getFieldname();

        $items = $this->model->getItems();
        $activeGroups = $this->model->getActiveGroups();

        $collectionMapping = $collectionToAdd = $this->model->getGroupCollectionMappings();

        // when the field is inheritable, check the parent collection mappings and skip the ones that are meant to be inherited
        $allowInherit = $this->model->getClass()->getAllowInherit();

        // check and exclude if an object is the top of the hierarchy
        // otherwise it wouldn't be able to distinguish whether the exact collections are from itself, rather from a common parent
        if ($allowInherit && ($parent = DataObject\Service::hasInheritableParentObject($object))) {
            $parentStore = $parent->{'get' . ucfirst($fieldname)}();
            if ($parentStore instanceof DataObject\Classificationstore) {
                $parentCollectionMapping = DataObject\Service::useInheritedValues(true, $parentStore->getGroupCollectionMappings(...));
                $collectionToAdd = array_diff_assoc($collectionToAdd, $parentCollectionMapping);
            }
        }

        $groupRows = [];

        foreach ($activeGroups as $activeGroupId => $enabled) {
            if ($enabled) {
                $groupRows[$activeGroupId] = [
                    'id' => $objectId,
                    'groupId' => $activeGroupId,
                    'fieldname' => $fieldname,
                ];
            }
        }

        $dataRows = [];
        $alreadySavedGroups = [];

        // the loop below resolves a key config per key, which on a cold cache - a save that did
        // not load the store first - would be a statement each
        DefinitionCache::load($this->getItemKeyIds($items, $activeGroups));

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
                        'id' => $objectId,
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
                        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

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

                    $dataRows[$this->getDataRowKey($data)] = $data;
                    $alreadySavedGroups[] = $groupId;
                }
            }
        }

        // Adds a placeholder to persist collectionId by adding the first field of the group
        // that belongs to a collection with NULL values
        foreach ($collectionToAdd as $groupId => $collectionId) {
            // Ignore the groups that are already saved and those without any collection id
            if ($collectionId && !in_array($groupId, $alreadySavedGroups)) {
                $group = GroupConfig::getById($groupId);
                $groupKeys = $group?->getRelations();
                if (!$groupKeys) {
                    continue;
                }
                $firstKey = reset($groupKeys);
                $keyId = $firstKey->getKeyId();
                $keyConfig = DefinitionCache::get($keyId);
                $data = [
                    'id' => $objectId,
                    'collectionId' => $collectionId,
                    'groupId' => $groupId,
                    'keyId' => $keyId,
                    'fieldname' => $fieldname,
                    'language' => 'default',
                    'type' => $keyConfig->getType(),
                    'value' => null,
                    'value2' => null,
                ];
                $dataRows[$this->getDataRowKey($data)] = $data;
            }
        }

        $this->persistRows($groupRows, $dataRows);
    }

    /**
     * Writes only the rows that differ: the stored rows for (object, fieldname) are read back and
     * diffed against the rows above, and what remains is emitted as multi-row statements. The
     * stored outcome is the one the former delete-and-reinsert produced, row for row.
     *
     * @param array<int, array<string, mixed>> $groupRows groupId => group activation row
     * @param array<string, array<string, mixed>> $dataRows row key => data row
     *
     * @throws Exception
     */
    private function persistRows(array $groupRows, array $dataRows): void
    {
        $objectId = $this->model->getObject()->getId();
        $fieldname = $this->model->getFieldname();
        $groupsTable = $this->getGroupsTableName();
        $dataTable = $this->getDataTableName();

        $storedGroupIds = $this->db->fetchFirstColumn(
            'SELECT `groupId` FROM `' . $groupsTable . '` WHERE `id` = ? AND `fieldname` = ?',
            [$objectId, $fieldname]
        );
        $storedDataRows = [];
        foreach ($this->db->fetchAllAssociative(
            'SELECT * FROM `' . $dataTable . '` WHERE `id` = ? AND `fieldname` = ?',
            [$objectId, $fieldname]
        ) as $storedRow) {
            $storedDataRows[$this->getDataRowKey($storedRow)] = $storedRow;
        }

        $groupIdsToDelete = array_map('intval', array_values(array_diff($storedGroupIds, array_keys($groupRows))));
        $groupIdsToDeleteMap = array_fill_keys($groupIdsToDelete, true);

        $dataRowsToWrite = [];
        foreach ($dataRows as $rowKey => $row) {
            if (!isset($storedDataRows[$rowKey]) || $this->dataRowDiffers($storedDataRows[$rowKey], $row)) {
                $dataRowsToWrite[] = $row;
            }
        }

        $dataRowsToDelete = [];
        foreach ($storedDataRows as $rowKey => $storedRow) {
            // a deactivated group's rows go with it through the ON DELETE CASCADE
            if (!isset($dataRows[$rowKey]) && !isset($groupIdsToDeleteMap[(int) $storedRow['groupId']])) {
                $dataRowsToDelete[] = $storedRow;
            }
        }

        // group rows are written first for the composite foreign key, upserted as before, and the
        // obsolete ones last. The obsolete data rows go before the replacements: the diff above
        // tells two rows apart by the exact spelling of their language, while the primary key
        // reads them under a case-insensitive collation, so a language stored as `en` and written
        // back as `EN` is scheduled as a write and a delete of what is one and the same row - and
        // only deleting first leaves the replacement standing.
        $this->insertRows(
            $groupsTable,
            self::GROUP_COLUMNS,
            array_diff_key($groupRows, array_flip($storedGroupIds)),
            ' ON DUPLICATE KEY UPDATE `id` = VALUES(`id`)'
        );
        $this->deleteDataRows($dataTable, $dataRowsToDelete, $objectId, $fieldname);
        $this->writeDataRows($dataTable, $dataRowsToWrite);
        $this->deleteGroupRows($groupsTable, $groupIdsToDelete, $objectId, $fieldname);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function getDataRowKey(array $row): string
    {
        return $row['groupId'] . '-' . $row['keyId'] . '-' . $row['language'];
    }

    /**
     * @param array<string, mixed> $storedRow
     * @param array<string, mixed> $row
     */
    private function dataRowDiffers(array $storedRow, array $row): bool
    {
        foreach (self::DATA_VALUE_COLUMNS as $column) {
            if ($this->normalizeColumnValue($storedRow[$column]) !== $this->normalizeColumnValue($row[$column])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The stored value of a column as the driver reads it back, so that a row written as an int
     * or a bool is not seen as changed on the next save.
     */
    private function normalizeColumnValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function writeDataRows(string $table, array $rows): void
    {
        $assignments = [];
        foreach (self::DATA_VALUE_COLUMNS as $column) {
            $assignments[] = '`' . $column . '` = VALUES(`' . $column . '`)';
        }

        $this->insertRows($table, self::DATA_COLUMNS, $rows, ' ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments));
    }

    /**
     * @param string[] $columns
     * @param array<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function insertRows(string $table, array $columns, array $rows, string $onDuplicate = ''): void
    {
        if (!$rows) {
            return;
        }

        $quotedColumns = array_map(static fn (string $column) => '`' . $column . '`', $columns);
        $rowPlaceholder = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $statement = 'INSERT INTO `' . $table . '` (' . implode(',', $quotedColumns) . ') VALUES ';
        $byteBudget = $this->getWriteBatchByteBudget();

        $parameters = [];
        $rowCount = 0;
        $size = 0;

        foreach ($rows as $row) {
            $rowParameters = [];
            $rowSize = 0;
            foreach ($columns as $column) {
                $rowParameters[] = $row[$column];
                $rowSize += $this->getParameterSize($row[$column]);
            }

            // a row of its own is emitted whatever it weighs, which is what the former
            // statement per row did as well
            if ($rowCount > 0 && ($rowCount >= self::WRITE_BATCH_SIZE || $size + $rowSize > $byteBudget)) {
                $this->executeInsert($statement, $rowPlaceholder, $rowCount, $parameters, $onDuplicate);

                $parameters = [];
                $rowCount = 0;
                $size = 0;
            }

            foreach ($rowParameters as $parameter) {
                $parameters[] = $parameter;
            }
            $rowCount++;
            $size += $rowSize;
        }

        $this->executeInsert($statement, $rowPlaceholder, $rowCount, $parameters, $onDuplicate);
    }

    /**
     * @param array<mixed> $parameters
     *
     * @throws Exception
     */
    private function executeInsert(
        string $statement,
        string $rowPlaceholder,
        int $rowCount,
        array $parameters,
        string $onDuplicate
    ): void {
        $this->db->executeStatement(
            $statement . implode(',', array_fill(0, $rowCount, $rowPlaceholder)) . $onDuplicate,
            $parameters
        );
    }

    /**
     * What a parameter is worth against the packet budget: the bytes of its value, plus the
     * placeholder and the separators it travels with.
     */
    private function getParameterSize(mixed $value): int
    {
        return (is_string($value) ? strlen($value) : 8) + 4;
    }

    /**
     * What one multi-row statement may carry: half of what the server accepts, so that a worst
     * case in which quoting doubles every byte of a value still fits into a single packet. A row
     * count alone does not bound a statement - both value columns are LONGTEXT, and a key without
     * a length limit can fill them.
     *
     * The limit is read once per process: it is a server setting, and asking for it on every save
     * would cost a statement of the kind this batching saves.
     *
     * @throws Exception
     */
    private function getWriteBatchByteBudget(): int
    {
        if (self::$writeBatchByteBudget === null) {
            $maxAllowedPacket = (int) $this->db->fetchOne('SELECT @@max_allowed_packet');

            self::$writeBatchByteBudget = $maxAllowedPacket > 0
                ? intdiv($maxAllowedPacket, 2)
                : self::WRITE_BATCH_FALLBACK_BYTES;
        }

        return self::$writeBatchByteBudget;
    }

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function deleteDataRows(string $table, array $rows, ?int $objectId, string $fieldname): void
    {
        foreach (array_chunk($rows, self::WRITE_BATCH_SIZE) as $chunk) {
            $parameters = [$objectId, $fieldname];
            foreach ($chunk as $row) {
                $parameters[] = $row['groupId'];
                $parameters[] = $row['keyId'];
                $parameters[] = $row['language'];
            }

            $conditions = array_fill(0, count($chunk), '(`groupId` = ? AND `keyId` = ? AND `language` = ?)');

            $this->db->executeStatement(
                'DELETE FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? AND ('
                . implode(' OR ', $conditions) . ')',
                $parameters
            );
        }
    }

    /**
     * @param int[] $groupIds
     *
     * @throws Exception
     */
    private function deleteGroupRows(string $table, array $groupIds, ?int $objectId, string $fieldname): void
    {
        foreach (array_chunk($groupIds, self::WRITE_BATCH_SIZE) as $chunk) {
            $this->db->executeStatement(
                'DELETE FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? AND `groupId` IN (?)',
                [$objectId, $fieldname, $chunk],
                [ParameterType::INTEGER, ParameterType::STRING, ArrayParameterType::INTEGER]
            );
        }
    }

    public function delete(): void
    {
        $object = $this->model->getObject();
        $objectId = $object->getId();
        $dataTable = $this->getDataTableName();
        $groupsTable = $this->getGroupsTableName();

        // remove relations
        $this->db->delete($dataTable, ['id' => $objectId]);
        $this->db->delete($groupsTable, ['id' => $objectId]);
    }

    /**
     * @throws Exception
     */
    public function load(): void
    {
        $classificationStore = $this->model;
        $object = $this->model->getObject();
        $dataTableName = $this->getDataTableName();
        $objectId = $object->getId() ?? 0;
        $fieldname = $this->model->getFieldname();
        $groupsTableName = $this->getGroupsTableName();

        $query = 'SELECT * FROM ' . $groupsTableName . ' WHERE id = ' . $objectId . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAllAssociative($query);
        $list = [];

        foreach ($data as $item) {
            $list[$item['groupId']] = true;
        }

        $query = 'SELECT * FROM ' . $dataTableName . ' WHERE id = ' . $objectId . ' AND fieldname = ' . $this->db->quote($fieldname);

        $data = $this->db->fetchAllAssociative($query);

        $groupCollectionMapping = [];

        /** @var MarshallerService $marshallerService */
        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);
        $relationCache->load(array_keys($list));

        // the relations above bring the key configs of the keys that are still in their group with
        // them; the ones of values whose key was removed from the group are read here, in one go,
        // so that the fallback below does not resolve them a statement at a time
        DefinitionCache::load($this->getStoredKeyIds($data, $list));

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

            $fd = $relationCache->getFieldDefinition($groupId, $keyId);

            if (!$fd) {
                // the key is no longer in the group - its value is still read through the key config, if that exists
                $keyConfig = DefinitionCache::get($keyId);
                if (!$keyConfig) {
                    Logger::error('Could not resolve key with ID: ' . $keyId);

                    continue;
                }

                $fd = Service::getFieldDefinitionFromKeyConfig($keyConfig);
            }

            if ($fd instanceof NormalizerInterface) {
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

    /**
     * The key ids held by the items of an active group, which is the set the save resolves a key
     * config for.
     *
     * @param array $items structure: [groupId][keyId][language] = value
     * @param array<int, bool> $activeGroups
     *
     * @return int[]
     */
    private function getItemKeyIds(array $items, array $activeGroups): array
    {
        $keyIds = [];

        foreach ($items as $groupId => $keys) {
            if (!isset($activeGroups[$groupId])) {
                continue;
            }

            foreach (array_keys($keys) as $keyId) {
                $keyIds[] = (int) $keyId;
            }
        }

        return $keyIds;
    }

    /**
     * The key ids of the stored rows that belong to an active group, which is the set the load
     * resolves a key config for.
     *
     * @param array<array<string, mixed>> $rows
     * @param array<int, bool> $activeGroups
     *
     * @return int[]
     */
    private function getStoredKeyIds(array $rows, array $activeGroups): array
    {
        $keyIds = [];

        foreach ($rows as $row) {
            if (isset($activeGroups[$row['groupId']])) {
                $keyIds[] = (int) $row['keyId'];
            }
        }

        return $keyIds;
    }

    public function createUpdateTable(): void
    {
        $groupsTable = $this->getGroupsTableName();
        $dataTable = $this->getDataTableName();

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $groupsTable . '` (
            `id` INT(11) UNSIGNED NOT NULL,
            `groupId` INT(11) UNSIGNED NOT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            PRIMARY KEY (`id`, `fieldname`, `groupId`),
            CONSTRAINT `'.self::getForeignKeyName($groupsTable, 'id').'` FOREIGN KEY (`id`) REFERENCES `objects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `'.self::getForeignKeyName($groupsTable, 'groupId').'` FOREIGN KEY (`groupId`) REFERENCES `classificationstore_groups` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;');

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $dataTable . '` (
            `id` INT(11) UNSIGNED NOT NULL,
            `collectionId` BIGINT(20) NULL,
            `groupId` INT(11) UNSIGNED NOT NULL,
            `keyId` BIGINT(20) NOT NULL,
            `value` LONGTEXT NULL,
	        `value2` LONGTEXT NULL,
            `fieldname` VARCHAR(70) NOT NULL,
            `language` VARCHAR(10) NOT NULL,
            `type` VARCHAR(50) NULL,
            PRIMARY KEY (`id`, `fieldname`, `groupId`, `keyId`, `language`),
            INDEX `keyId` (`keyId`),
            INDEX `language` (`language`),
            INDEX `groupKeys` (`id`, `fieldname`, `groupId`),
            CONSTRAINT `'.self::getForeignKeyName($dataTable, 'id').'` FOREIGN KEY (`id`) REFERENCES `objects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `'.self::getForeignKeyName($dataTable, 'id__fieldname__groupId').'` FOREIGN KEY (`id`, `fieldname`, `groupId`) REFERENCES `' . $groupsTable . '` (`id`, `fieldname`, `groupId`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;');

        $this->tableDefinitions = [];

        $this->handleEncryption($this->model->getClass(), [$groupsTable, $dataTable]);
    }
}
