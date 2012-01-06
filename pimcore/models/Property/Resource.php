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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Property_Resource extends Pimcore_Model_Resource_Abstract {


    public function getRawData(){
        $cid = $this->model->getCid();
        $type = $this->model->getType();
        $name = $this->model->getName();
        $raw = null;
        if($cid){
            $data = $this->db->fetchRow("SELECT * FROM properties WHERE type=? AND cid = ? AND name=?",array($type,$cid,$name) );
            $raw = $data['data'];
        }
        return $raw;
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $data = $this->model->getData();

        if ($this->model->getType() == "object" || $this->model->getType() == "asset" || $this->model->getType() == "document") {

            if ($data instanceof Element_Interface) {
                $data = $data->getId();
            }
            else {
                $data = null;
            }
        }


        if (is_array($data) || is_object($data)) {
            $data = Pimcore_Tool_Serialize::serialize($data);
        }

        $saveData = array(
            "cid" => $this->model->getCid(),
            "ctype" => $this->model->getCtype(),
            "cpath" => $this->model->getCpath(),
            "name" => $this->model->getName(),
            "type" => $this->model->getType(),
            "inheritable" => (int)$this->model->getInheritable(),
            "data" => $data
        );

        try {
            $this->db->insert("properties", $saveData);
        }
        catch (Exception $e) {
            $this->db->update("properties", $saveData, $this->db->quoteInto("name = ?", $this->model->getName()) . " AND " . $this->db->quoteInto("cid = ?", $this->model->getCid()) . " AND " . $this->db->quoteInto("ctype = ?", $this->model->getCtype()));
        }
    }
}
