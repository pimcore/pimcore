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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Version_Resource extends Pimcore_Model_Resource_Abstract {

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
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM versions WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new Exception("version with id " . $id . " not found");
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

        try {
            $this->db->insert("versions", $data);
            $this->model->setId($this->db->lastInsertId());
        }
        catch (Exception $e) {
            $this->db->update("versions", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
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
    public function maintenanceGetOutdatedVersions ($elementTypes) {

        $versionIds = array();

        if(!empty($elementTypes)) {
            foreach ($elementTypes as $elementType) {
                if($elementType["days"] > 0) {
                    // by days
                    $deadline = time() - ($elementType["days"] * 86400);
                    $tmpVersionIds = $this->db->fetchCol("SELECT id FROM versions as a WHERE (ctype = ? AND date < ?)", array($elementType["elementType"], $deadline));
                    $versionIds = array_merge($versionIds, $tmpVersionIds);
                } else {
                    // by steps
                    $elementIds = $this->db->fetchCol("SELECT cid,count(*) as amount FROM versions WHERE ctype = ? GROUP BY cid HAVING amount > ?", array($elementType["elementType"], $elementType["steps"]));
                    foreach ($elementIds as $elementId) {
                        $elementVersions = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? and ctype = ? ORDER BY date DESC LIMIT " . $elementType["steps"] . ",1000000", array($elementId, $elementType["elementType"]));
                        $versionIds = array_merge($versionIds, $elementVersions);

                        // call the garbage collector if memory consumption is > 100MB
                        if(memory_get_usage() > 100000000) {
                            Pimcore::collectGarbage();
                            $versionIds = array_unique($versionIds);
                        }
                    }

                    $versionIds = array_unique($versionIds);
                }
            }
        }

        return $versionIds;
    }
}
