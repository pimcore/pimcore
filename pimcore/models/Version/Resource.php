<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Version
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Version;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("versions");
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM versions WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new \Exception("version with id " . $id . " not found");
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $version = get_object_vars($this->model);

        foreach ($version as $key => $value) {
            if (in_array($key, $this->validColumns)) {
                if(is_bool($value)) {
                    $value = (int) $value;
                }
                
                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("versions", $data);

        $lastInsertId = $this->db->lastInsertId();
        if(!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        return $this->model->getId();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("versions", $this->db->quoteInto("id = ?", $this->model->getId() ));
    }

    /**
     * @deprecated
     * @param integer $days
     * @return array
     */
    public function getOutdatedVersionsDays($days) {
        $deadline = time() - (intval($days) * 86400);

        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? AND date < ?", array($this->model->getCid(), $this->model->getCtype(), $deadline));
        return $versionIds;
    }

    /**
     * @param $steps
     * @return array
     */
    public function getOutdatedVersionsSteps($steps) {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? ORDER BY date DESC LIMIT " . intval($steps) . ",1000000", array($this->model->getCid(), $this->model->getCtype()));
        return $versionIds;
    }

    /**
     * @param $elementTypes
     * @return array
     */
    public function maintenanceGetOutdatedVersions ($elementTypes, $ignoreIds = array()) {

        $ignoreIdsList = implode(",", $ignoreIds);
        if(!$ignoreIdsList) {
            $ignoreIdsList = "0"; // set a default to avoid SQL errors (there's no version with ID 0)
        }
        $versionIds = array();

        \Logger::debug("ignore ID's: " . $ignoreIdsList);

        if(!empty($elementTypes)) {
            $count = 0;
            $stop = false;
            foreach ($elementTypes as $elementType) {

                if($elementType["days"] > 0) {
                    // by days
                    $deadline = time() - ($elementType["days"] * 86400);
                    $tmpVersionIds = $this->db->fetchCol("SELECT id FROM versions as a WHERE (ctype = ? AND date < ?) AND NOT public AND id NOT IN (" . $ignoreIdsList . ")", array($elementType["elementType"], $deadline));
                    $versionIds = array_merge($versionIds, $tmpVersionIds);
                } else {
                    // by steps
                    $elementIds = $this->db->fetchCol("SELECT cid,count(*) as amount FROM versions WHERE ctype = ? AND NOT public AND id NOT IN (" . $ignoreIdsList . ") GROUP BY cid HAVING amount > ?", array($elementType["elementType"], $elementType["steps"]));
                    foreach ($elementIds as $elementId) {
                        $count++;
                        \Logger::info($elementId . "(object " . $count . ") Vcount " . count($versionIds));
                        $elementVersions = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? ORDER BY date DESC LIMIT " . $elementType["steps"] . ",1000000", array($elementId, $elementType["elementType"]));

                        $versionIds = array_merge($versionIds, $elementVersions);

                        // call the garbage collector if memory consumption is > 100MB
                        if(memory_get_usage() > 100000000 && ($count % 100 == 0)) {
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
        \Logger::info("return " .  count($versionIds) . " ids\n");
        return $versionIds;
    }
}
