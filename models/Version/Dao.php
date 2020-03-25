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
 * @package    Version
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Version;

use Pimcore\Logger;
use Pimcore\Model;

/**
 * @property \Pimcore\Model\Version $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow('SELECT * FROM versions WHERE id = ?', $id);

        if (!$data['id']) {
            throw new \Exception('version with id ' . $id . ' not found');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return int
     *
     * @todo: $data could be undefined
     */
    public function save()
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

        $this->db->insertOrUpdate('versions', $data);

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        return $this->model->getId();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('versions', ['id' => $this->model->getId()]);
    }

    /**
     * @param Model\Version $version
     *
     * @return bool
     */
    public function isVersionUsedInScheduler($version)
    {
        $exists = $this->db->fetchOne('SELECT id FROM schedule_tasks WHERE version = ?', [$version->getId()]);

        return (bool) $exists;
    }

    /**
     * @param string $hash
     *
     * @return int|null
     */
    public function getBinaryFileIdForHash(string $hash): ?int
    {
        $id = $this->db->fetchOne('SELECT IFNULL(binaryFileId, id) FROM versions WHERE binaryFileHash = ? AND cid = ? ORDER BY id ASC LIMIT 1', [$hash, $this->model->getCid()]);
        if (!$id) {
            return null;
        }

        return (int)$id;
    }

    /**
     * @param string|null $hash
     *
     * @return bool
     */
    public function isBinaryHashInUse(?string $hash): bool
    {
        $count = $this->db->fetchOne('SELECT count(*) FROM versions WHERE binaryFileHash = ? AND cid = ?', [$hash, $this->model->getCid()]);
        $returnValue = ($count > 1);

        return $returnValue;
    }

    /**
     * @param array $elementTypes
     * @param array $ignoreIds
     *
     * @return array
     */
    public function maintenanceGetOutdatedVersions($elementTypes, $ignoreIds = [])
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
                if (isset($elementType['days']) && $elementType['days'] > 0) {
                    // by days
                    $deadline = time() - ($elementType['days'] * 86400);
                    $tmpVersionIds = $this->db->fetchCol('SELECT id FROM versions as a WHERE (ctype = ? AND date < ?) AND NOT public AND id NOT IN (' . $ignoreIdsList . ')', [$elementType['elementType'], $deadline]);
                    $versionIds = array_merge($versionIds, $tmpVersionIds);
                } else {
                    // by steps
                    $versionData = $this->db->executeQuery('SELECT cid, GROUP_CONCAT(id ORDER BY id DESC) AS versions FROM versions WHERE ctype = ? AND NOT public AND id NOT IN (' . $ignoreIdsList . ') GROUP BY cid HAVING COUNT(*) > ? LIMIT 1000', [$elementType['elementType'], $elementType['steps']]);
                    while ($versionInfo = $versionData->fetch()) {
                        $count++;
                        Logger::info($versionInfo['cid'] . '(object ' . $count . ') Vcount ' . count($versionIds));
                        $elementVersions = \array_slice(explode(',', $versionInfo['versions']), $elementType['steps']);

                        $versionIds = array_merge($versionIds, $elementVersions);

                        // call the garbage collector if memory consumption is > 100MB
                        if (memory_get_usage() > 100000000 && ($count % 100 == 0)) {
                            \Pimcore::collectGarbage();
                            sleep(1);
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

        return $versionIds;
    }
}
