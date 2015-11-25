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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Recyclebin\Item;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM recyclebin WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new \Exception("Recyclebin item with id " . $id . " not found");
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
            if (in_array($key, $this->getValidTableColumns("recyclebin"))) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert("recyclebin", $data);
            $this->model->setId($this->db->lastInsertId());
        }
        catch (\Exception $e) {
            \Logger::error($e);
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("recyclebin", $this->db->quoteInto("id = ?", $this->model->getId()));
    }
}
