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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Version;

/**
 * @internal
 *
 * @property \Pimcore\Model\Version $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * How many elements' versions maintenanceGetOutdatedVersions() ranks per query.
     */
    private const OUTDATED_VERSIONS_ELEMENTS_PER_QUERY = 1000;

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
     *
     * @return int[]
     */
    public function maintenanceGetOutdatedVersions(array $elementTypes): array
    {
        $versionIds = [];

        foreach ($elementTypes as $elementType) {
            if (isset($elementType['days'])) {
                // by days
                $deadline = time() - ($elementType['days'] * 86400);
                $tmpVersionIds = $this->db->fetchFirstColumn(
                    'SELECT a.id as id FROM versions AS a
                    LEFT JOIN schedule_tasks ON a.id = schedule_tasks.version
                    LEFT JOIN '. $elementType['elementType'] .'s AS element ON a.cid = element.id
                    WHERE a.ctype = ?
                    AND public = 0 AND autosave = 0
                    AND element.modificationDate >= a.`date`
                    AND a.`date` < ? AND IFNULL(active, 0) = 0',
                    [
                        $elementType['elementType'],
                        $deadline,
                    ]
                );
                $versionIds = array_merge($versionIds, $tmpVersionIds);
            } else {
                $countsPerCid = [];
                $kept = $elementType['steps'] + 1;

                // Only an element with more versions than are kept can have outdated ones. Finding those elements
                // reads the (ctype, cid) index alone, so the ranking below numbers their versions instead of every
                // version of the type. Counting all of an element's versions, not just the eligible ones, can only
                // add elements, never miss one. The ranking gets the elements as a list, because MySQL drives a join
                // or IN subquery from the versions table and so would still read all of it.
                $cids = $this->db->fetchFirstColumn(
                    'SELECT cid FROM versions WHERE ctype = ? GROUP BY cid HAVING COUNT(*) > ?',
                    [$elementType['elementType'], $kept]
                );

                // ranked by the version count, the order the states were saved in: a version that is published in
                // place (see AbstractElement::doSaveVersion()) keeps its id but takes the element's new version count,
                // so it isn't necessarily among the newest ids. The version of the element's current state is always kept
                $sql = '
                    SELECT sub.cid as cid, sub.id as id, sub.`date`
                    FROM (
                        SELECT id, cid, versions.`date`, versionCount,
                               ROW_NUMBER() OVER (PARTITION BY cid ORDER BY versionCount DESC, id DESC) AS rownumber
                        FROM versions
                        WHERE ctype = ? AND public = 0 AND autosave = 0 AND cid IN (?)
                    ) sub
                    LEFT JOIN schedule_tasks ON sub.id = schedule_tasks.version
                    LEFT JOIN '. $elementType['elementType'] .'s AS element ON sub.cid = element.id
                    WHERE rownumber > ? AND IFNULL(active, 0) = 0 AND element.modificationDate >= sub.`date`
                    AND NOT (sub.versionCount = element.versionCount AND sub.`date` = element.modificationDate)
                ';

                foreach (array_chunk($cids, self::OUTDATED_VERSIONS_ELEMENTS_PER_QUERY) as $cidChunk) {
                    $iterator = $this->db->iterateAssociative(
                        $sql,
                        [
                            $elementType['elementType'],
                            array_map('intval', $cidChunk),
                            $kept,
                        ],
                        [
                            ParameterType::STRING,
                            ArrayParameterType::INTEGER,
                            ParameterType::INTEGER,
                        ]
                    );

                    foreach ($iterator as $versionInfo) {
                        $cid = $versionInfo['cid'];
                        if (!isset($countsPerCid[$cid])) {
                            $countsPerCid[$cid] = 0;
                        }
                        $countsPerCid[$cid]++;
                        $versionIds[] = $versionInfo['id'];
                    }
                }

                foreach ($countsPerCid as $cid => $countPerCid) {
                    Logger::info($elementType['elementType'] . ' id: ' . $cid . ' Vcount: ' . $countPerCid);
                }
            }
        }

        Logger::info('return ' .  count($versionIds) . " ids\n");

        return array_map('intval', $versionIds);
    }

    public function getOrphanedVersionsAndOutdatedAutoSave(array $elementTypes): array
    {
        $results = [];

        $autoSaveDateCleanup = \Carbon\Carbon::now();
        $autoSaveDateCleanup->subHours(72);

        foreach ($elementTypes as $elementType) {
            $table = $elementType['elementType'] . 's';
            $type = $elementType['elementType'];

            // Two selects instead of one WHERE with OR: the OR kept MySQL from using any index, so every run read
            // the whole versions table. Now the orphan check reads the (ctype, cid) index and the auto-save check
            // the autoSave index. UNION removes duplicates, so the result is the same set as before.
            $sql = "
                SELECT versions.id
                FROM versions
                LEFT JOIN {$table} AS element ON element.id = versions.cid
                WHERE versions.ctype = :ctype AND element.id IS NULL
                UNION
                SELECT versions.id
                FROM versions
                WHERE autoSave = 1 AND date < :autoSaveDateCleanup
            ";

            $rows = $this->db->fetchAllAssociative(
                $sql,
                ['ctype' => $type, 'autoSaveDateCleanup' => $autoSaveDateCleanup->getTimestamp()]
            );
            $results = array_merge($results, $rows);
        }

        return array_column($results, 'id');
    }

    public function deleteVersions(array $ids, array $elementTypes, int $chunkSize = 1000): void
    {

        foreach ($elementTypes as $elementType) {
            if ($elementType['disable_events']) {
                $idChunks = array_chunk($ids, $chunkSize);

                foreach ($idChunks as $chunk) {
                    $versionIds = implode(',', $chunk);

                    $query = "DELETE FROM versions WHERE id IN ($versionIds)";
                    $this->db->executeQuery($query);
                }
            } else {
                foreach ($ids as $id) {
                    $version = Version::getById($id);
                    if ($version) {
                        $version->delete();
                    }
                }
            }
        }
    }
}
