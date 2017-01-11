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
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Editlock;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Element\Editlock $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $cid
     * @param $ctype
     * @throws \Exception
     */
    public function getByElement($cid, $ctype)
    {
        $data = $this->db->fetchRow("SELECT * FROM edit_lock WHERE cid = ? AND ctype = ?", [$cid, $ctype]);

        if (!$data["id"]) {
            throw new \Exception("Lock with cid " . $cid . " and ctype " . $ctype . " not found");
        }

        $this->assignVariablesToModel($data);

        // add elements path
        $element = Model\Element\Service::getElementById($ctype, $cid);
        if ($element) {
            $this->model->setCpath($element->getRealFullPath());
        }
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save()
    {
        $version = get_object_vars($this->model);

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("edit_lock"))) {
                $data[$key] = $value;
            }
        }

        //var_dump($data);exit;
        $this->db->insertOrUpdate("edit_lock", $data);

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->delete("edit_lock", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @param $sessionId
     */
    public function clearSession($sessionId)
    {
        $this->db->delete("edit_lock", $this->db->quoteInto("sessionId = ?", $sessionId));
    }
}
