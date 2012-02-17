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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Sanitycheck_Resource extends Pimcore_Model_Resource_Abstract {


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
        $this->validColumns = $this->getValidTableColumns("sanitycheck");
    }


    /**
     * Save to database
     *
     * @return void
     */
    public function save() {

        $sanityCheck = get_object_vars($this->model);

        foreach ($sanityCheck as $key => $value) {
            if (in_array($key, $this->validColumns)) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert("sanitycheck", $data);
        }
        catch (Exception $e) {
           //probably duplicate
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("sanitycheck", $this->db->quoteInto("id = ?", $this->model->getId()) . " AND " . $this->db->quoteInto("type = ?", $this->model->getType()));
    }

    public  function getNext(){

        $data = $this->db->fetchRow("SELECT * FROM sanitycheck LIMIT 1");
        if (is_array($data)) {
            $this->assignVariablesToModel($data);
        }  


    }

}