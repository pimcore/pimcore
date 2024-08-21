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

        Helper::upsert($this->db, 'versions', $data, $this->getPrimaryKey('versions'));

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
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
        $ignoreIdsList = implode(',', $ignoreIds);
        if (!$ignoreIdsList) {
            $ignoreIdsList = '0'; // set a default to avoid SQL errors (there's no version with ID 0)
        }
        $versionIds = [];

        Logger::debug("ignore ID's: " . $ignoreIdsList);

        if (!empty($elementTypes)) {
            $count = 0;
            $stop = false;
            foreach ($elementTypes as $elementType) {
                if (isset($elementType['days'])) {
                    // by days
                    $deadline = time() - ($elementType['days'] * 86400);
                    $tmpVersionIds = $this->db->fetchFirstColumn('SELECT id FROM versions as a WHERE ctype = ? AND date < ? AND public=0 AND id NOT IN (' . $ignoreIdsList . ')', [$elementType['elementType'], $deadline]);
                    $versionIds = array_merge($versionIds, $tmpVersionIds);
                } else {
                    // by steps
                    $versionData = $this->db->executeQuery('SELECT cid FROM versions WHERE ctype = ? AND public=0 AND id NOT IN (' . $ignoreIdsList . ') GROUP BY cid HAVING COUNT(*) > ? LIMIT 1000', [$elementType['elementType'], $elementType['steps'] + 1]);
                    while ($versionInfo = $versionData->fetchAssociative()) {
                        $count++;
                        $elementVersions = $this->db->fetchFirstColumn('SELECT id FROM versions WHERE cid=? AND ctype = ? AND public=0 AND id NOT IN ('.$ignoreIdsList.') ORDER BY id DESC LIMIT '.($elementType['steps'] + 1).', '.PHP_INT_MAX, [$versionInfo['cid'], $elementType['elementType']]);

                        $versionIds = array_merge($versionIds, $elementVersions);

                        Logger::info($versionInfo['cid'].'(object '.$count.') Vcount '.count($versionIds));

                        // call the garbage collector if memory consumption is > 100MB
                        if (memory_get_usage() > 100000000 && ($count % 100 == 0)) {
                            Pimcore::collectGarbage();
                        }

                        if (count($versionIds) > 1000) {
                            $stop = true;

                            break;
                        }
                    }

                    if ($stop) {
                        break;
                    }
                }
            }
        }
        Logger::info('return ' .  count($versionIds) . " ids\n");

        return array_map('intval', $versionIds);
    }
}
