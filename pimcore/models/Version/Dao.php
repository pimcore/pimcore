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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Version;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @property \Pimcore\Model\Version $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM versions WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new \Exception("version with id " . $id . " not found");
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
        $version = get_object_vars($this->model);

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("versions"))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("versions", $data);

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
        $this->db->delete("versions", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @deprecated
     * @param integer $days
     * @return array
     */
    public function getOutdatedVersionsDays($days)
    {
        $deadline = time() - (intval($days) * 86400);

        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? AND date < ?", [$this->model->getCid(), $this->model->getCtype(), $deadline]);

        return $versionIds;
    }

    /**
     * @param $steps
     * @return array
     */
    public function getOutdatedVersionsSteps($steps)
    {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? ORDER BY date DESC LIMIT " . intval($steps) . ",1000000", [$this->model->getCid(), $this->model->getCtype()]);

        return $versionIds;
    }

    /**
     * @param $elementTypes
     * @param array $ignoreIds
     * @return array
     */
    public function maintenanceGetOutdatedVersions($elementTypes, $ignoreIds = [])
    {
        $ignoreIdsList = implode(",", $ignoreIds);
        if (!$ignoreIdsList) {
            $ignoreIdsList = "0"; // set a default to avoid SQL errors (there's no version with ID 0)
        }
        $versionIds = [];

        Logger::debug("ignore ID's: " . $ignoreIdsList);

        if (!empty($elementTypes)) {
            $count = 0;
            $stop = false;
            foreach ($elementTypes as $elementType) {
                if ($elementType["days"] > 0) {
                    // by days
                    $deadline = time() - ($elementType["days"] * 86400);
                    $tmpVersionIds = $this->db->fetchCol("SELECT id FROM versions as a WHERE (ctype = ? AND date < ?) AND NOT public AND id NOT IN (" . $ignoreIdsList . ")", [$elementType["elementType"], $deadline]);
                    $versionIds = array_merge($versionIds, $tmpVersionIds);
                } else {
                    // by steps
                    $elementIds = $this->db->fetchCol("SELECT cid,count(*) as amount FROM versions WHERE ctype = ? AND NOT public AND id NOT IN (" . $ignoreIdsList . ") GROUP BY cid HAVING amount > ?", [$elementType["elementType"], $elementType["steps"]]);
                    foreach ($elementIds as $elementId) {
                        $count++;
                        Logger::info($elementId . "(object " . $count . ") Vcount " . count($versionIds));
                        $elementVersions = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? ORDER BY date DESC LIMIT " . $elementType["steps"] . ",1000000", [$elementId, $elementType["elementType"]]);

                        $versionIds = array_merge($versionIds, $elementVersions);

                        // call the garbage collector if memory consumption is > 100MB
                        if (memory_get_usage() > 100000000 && ($count % 100 == 0)) {
                            \Pimcore::collectGarbage();
                            sleep(1);

                            $versionIds = array_unique($versionIds);
                        }

                        if (count($versionIds) > 1000) {
                            $stop = true;
                            break;
                        }
                    }

                    $versionIds = array_unique($versionIds);

                    if ($stop) {
                        break;
                    }
                }
            }
        }
        Logger::info("return " .  count($versionIds) . " ids\n");

        return $versionIds;
    }
}
