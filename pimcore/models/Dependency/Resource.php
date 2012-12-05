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
 * @package    Dependency
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Dependency_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * List of valid columns in database table
     * This is used for automatic matching the objects properties to the database
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
        $this->validColumns = $this->getValidTableColumns("dependencies");
    }

    /**
     * Loads the relations for the given sourceId and type
     *
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function getBySourceId($id = null, $type = null) {

        if ($id && $type) {
            $this->model->setSourceId($id);
            $this->model->setSourceType($id);
        }

        // requires
        $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE sourceid = ? AND sourcetype = ?", array($this->model->getSourceId(), $this->model->getSourceType()));

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->addRequirement($d["targetid"], $d["targettype"]);
            }
        }

        // required by
        $data = array();
        $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?", array($this->model->getSourceId(), $this->model->getSourceType()));

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $d) {
                $this->model->requiredBy[] = array(
                    "id" => $d["sourceid"],
                    "type" => $d["sourcetype"]
                );
            }
        }
    }

    /**
     * Clear all relations in the database
     * @param Element_Interface $element
     */
    public function cleanAllForElement($element) {
        try {

            $id = $element->getId();
            $type = Element_Service::getElementType($element);

            //schedule for sanity check
            $data = $this->db->fetchAll("SELECT * FROM dependencies WHERE targetid = ? AND targettype = ?", array($id, $type));
            if (is_array($data)) {
                foreach ($data as $row) {
                    $sanityCheck = new Element_Sanitycheck();
                    $sanityCheck->setId($row['sourceid']);
                    $sanityCheck->setType($row['sourcetype']);
                    $sanityCheck->save();
                }
            }

            $this->db->delete("dependencies", $this->db->quoteInto("sourceid = ?", $id) . " AND " . $this->db->quoteInto("sourcetype = ?", $type));
            $this->db->delete("dependencies", $this->db->quoteInto("targetid = ?", $id) . " AND " . $this->db->quoteInto("targettype = ?", $type));
        }
        catch (Exception $e) {
            Logger::error($e);
        }
    }


    /**
     * Clear all relations in the database for current source id
     *
     * @return void
     */
    public function clear() {

        try {
            $this->db->delete("dependencies", $this->db->quoteInto("sourceid = ?", $this->model->getSourceId()) . " AND " . $this->db->quoteInto("sourcetype = ?", $this->model->getSourceType()));
        }
        catch (Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * Save to database
     *
     * @return void
     */
    public function save() {
        try {
            foreach ($this->model->getRequires() as $r) {

                try {
                    if ($r["id"] && $r["type"]) {
                        $this->db->insert("dependencies", array(
                            "sourceid" => $this->model->getSourceId(),
                            "sourcetype" => $this->model->getSourceType(),
                            "targetid" => $r["id"],
                            "targettype" => $r["type"]
                        ));
                    }
                }
                catch (Exception $e) {
                    Logger::error($e);
                }
            }
        } catch (Exception $e) {
            Logger::error($e);
        }
    }
}
