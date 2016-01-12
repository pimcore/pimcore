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

namespace Pimcore\Model\Tool\Email\Blacklist;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param $address
     * @throws \Exception
     */
    public function getByAddress($address) {
        $data = $this->db->fetchRow("SELECT * FROM email_blacklist WHERE address = ?", $address);

        if (!$data["address"]) {
            throw new \Exception("blacklist item with address " . $address . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $this->model->setModificationDate(time());
        if(!$this->model->getCreationDate()) {
            $this->model->setCreationDate(time());
        }

        $version = get_object_vars($this->model);

        // save main table
        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("email_blacklist"))) {

                if(is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("email_blacklist", $data);

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("email_blacklist", $this->db->quoteInto("address = ?", $this->model->getAddress() ));
    }
}
