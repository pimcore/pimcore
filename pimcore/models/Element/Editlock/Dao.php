<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Editlock;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

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
            if (in_array($key, $this->getValidTableColumns("edit_lock"))) {
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

    /**
     * @param $sessionId
     */
    public function clearSession($sessionId) {
        $this->db->delete("edit_lock", $this->db->quoteInto("sessionId = ?", $sessionId ));
    }
}
