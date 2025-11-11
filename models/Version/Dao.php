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

namespace Pimcore\Model\Version;

use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property \Pimcore\Model\Version $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM versions WHERE id = ?', [$id]);

        if (!$data) {
            throw new NotFoundException('version with id ' . $id . ' not found');
        }

        $data['public'] = (bool)$data['public'];
        $data['serialized'] = (bool)$data['serialized'];
        $data['autoSave'] = (bool)$data['autoSave'];
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     *
     * @todo: $data could be undefined
     */
    public function save(): int
    {
        $version = $this->model->getObjectVars();
        $data = [];

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('versions'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $lastInsertId = Helper::upsert($this->db, 'versions', $data, $this->getPrimaryKey('versions'));
        if ($lastInsertId !== null && !$this->model->getId()) {
            $this->model->setId((int) $lastInsertId);
        }

        return $this->model->getId();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('versions', ['id' => $this->model->getId()]);
    }

    public function isVersionUsedInScheduler(Model\Version $version): bool
    {
        $exists = $this->db->fetchOne('SELECT id FROM schedule_tasks WHERE active = 1 AND version = ?', [$version->getId()]);

        return (bool) $exists;
    }

    public function getBinaryFileIdForHash(string $hash): ?int
    {
        $id = $this->db->fetchOne('SELECT IFNULL(binaryFileId, id) FROM versions WHERE binaryFileHash = ? AND cid = ? AND storageType = ? ORDER BY id ASC LIMIT 1', [$hash, $this->model->getCid(), $this->model->getStorageType()]);
        if (!$id) {
            return null;
        }

        return (int)$id;
    }

    public function isBinaryHashInUse(?string $hash): bool
    {
        $count = $this->db->fetchOne('SELECT count(*) FROM versions WHERE binaryFileHash = ? AND cid = ?', [$hash, $this->model->getCid()]);
        $returnValue = ($count > 1);

        return $returnValue;
    }

    /**
     * @param list<array{elementType: string, days?: int, steps?: int}> $elementTypes
     * @param int[] $ignoreIds
     *
     * @return int[]
     */
    public function maintenanceGetOutdatedVersions(array $elementTypes, array $ignoreIds = []): array
    {
        $ignoreIdsQueryPart = '';
        if (!empty($ignoreIds)) {
            $ignoreIdsList = implode(',', $ignoreIds);
            $ignoreIdsQueryPart = ' AND id NOT IN (' . $ignoreIdsList . ')';
            Logger::debug("ignore ID's: " . $ignoreIdsList);
        }

        $versionIds = [];
        $count = 0;

        foreach ($elementTypes as $elementType) {
            if (isset($elementType['days'])) {
                // by days
                $deadline = time() - ($elementType['days'] * 86400);
                $tmpVersionIds = $this->db->fetchFirstColumn(
                    'SELECT id FROM versions AS a
                    WHERE ctype = ?
                    AND public = 0 ' . $ignoreIdsQueryPart . '
                    AND date < ?',
                    [$elementType['elementType'], $deadline]
                );
                $versionIds = array_merge($versionIds, $tmpVersionIds);
            } else {

                $limit = 1000;
                $offset = 0;
                $versionIds = [];
                
                do {
                    $countsPerCid = [];
                    
                    $sql = '
                        SELECT cid, id
                        FROM (
                            SELECT id, cid,
                                   ROW_NUMBER() OVER (PARTITION BY cid ORDER BY id DESC) AS rownumber
                            FROM versions
                            WHERE ctype = ? AND public = 0 ' . $ignoreIdsQueryPart . '
                        ) sub
                        WHERE rownumber > ?
                        LIMIT ? 
                        OFFSET ?
                    ';
                    
                    $elementVersions = $this->db->fetchAllAssociative(
                        $sql,
                        [
                            $elementType['elementType'],
                            $elementType['steps'] + 1,
                            $limit,
                            $offset,
                        ]
                    );
                                        
                    foreach ($elementVersions as $versionInfo) {
                        $cid = $versionInfo['cid'];
                        if (!isset($countsPerCid[$cid])) {
                            $countsPerCid[$cid] = 0;
                        }
                        $countsPerCid[$cid]++;
                        $versionIds[] = $versionInfo['id'];
                    }
                    
                    foreach ($countsPerCid as $cid => $countPerCid) {
                        Logger::info($elementType['elementType']. ' id: '. $cid . ' Vcount: ' . $countPerCid);
                    }

                    $offset += $limit;
                    
                } while (count($elementVersions) > 0);
            }
        }

        Logger::info('return ' .  count($versionIds) . " ids\n");

        return array_map('intval', $versionIds);
    }
}
