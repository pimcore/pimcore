<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM schedule_tasks WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("there is no task for the requested id");
        }
        $this->assignVariablesToModel($data);
    }


    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert("schedule_tasks", array());
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {

        $site = get_object_vars($this->model);

        foreach ($site as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("schedule_tasks"))) {

                if (is_array($value) || is_object($value)) {
                    $value = \Pimcore\Tool\Serialize::serialize($value);
                } else if(is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("schedule_tasks", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("schedule_tasks", $this->db->quoteInto("id = ?", $this->model->getId()));
    }
}
