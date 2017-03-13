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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\WorkflowState;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Element\WorkflowState $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $cid
     * @param $ctype
     * @param $workflowId
     * @throws \Exception
     */
    public function getByPrimary($cid, $ctype, $workflowId)
    {
        $data = $this->db->fetchRow("SELECT * FROM element_workflow_state WHERE cid = ? AND ctype = ? AND workflowId = ?", [$cid, $ctype, $workflowId]);

        if (!$data["cid"]) {
            throw new \Exception("WorkflowStatus item with cid " . $cid . " and ctype " . $ctype . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return boolean
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save()
    {
        $dataAttributes = get_object_vars($this->model);

        $data = [];
        foreach ($dataAttributes as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("element_workflow_state"))) {
                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("element_workflow_state", $data);

        return true;
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete("element_workflow_state", [
            "cid" => $this->model->getCid(),
            "ctype" => $this->model->getCtype()
        ]);
    }
}
