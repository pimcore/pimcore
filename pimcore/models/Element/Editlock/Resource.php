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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element\Editlock;

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
        $this->validColumns = $this->getValidTableColumns("edit_lock");
    }

    /**
     * @param $cid
     * @param $ctype
     * @throws \Exception
     */
    public function getByElement($cid, $ctype) {
        $data = $this->db->fetchRow("SELECT * FROM edit_lock WHERE cid = ? AND ctype = ?", array($cid, $ctype));

        if (!$data["id"]) {
            throw new \Exception("Lock with cid " . $cid . " and ctype " . $ctype . " not found");
        }

        $this->assignVariablesToModel($data);

        // add elements path
        $element = Model\Element\Service::getElementById($ctype, $cid);
        if($element) {
            $this->model->setCpath($element->getFullpath());
        }
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
                $data[$key] = $value;
            }
        }

        //var_dump($data);exit;
        $this->db->insertOrUpdate("edit_lock", $data);

        $lastInsertId = $this->db->lastInsertId();
        if(!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("edit_lock", $this->db->quoteInto("id = ?", $this->model->getId() ));
    }
}
