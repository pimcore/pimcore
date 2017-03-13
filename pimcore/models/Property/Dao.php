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
 * @package    Property
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Property;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Property $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @return null
     */
    public function getRawData()
    {
        $cid = $this->model->getCid();
        $type = $this->model->getType();
        $name = $this->model->getName();
        $raw = null;
        if ($cid) {
            $data = $this->db->fetchRow("SELECT * FROM properties WHERE type=? AND cid = ? AND name=?", [$type, $cid, $name]);
            $raw = $data['data'];
        }

        return $raw;
    }

    /**
     * Save object to database
     */
    public function save()
    {
        $data = $this->model->getData();

        if ($this->model->getType() == "object" || $this->model->getType() == "asset" || $this->model->getType() == "document") {
            if ($data instanceof Model\Element\ElementInterface) {
                $data = $data->getId();
            } else {
                $data = null;
            }
        }


        if (is_array($data) || is_object($data)) {
            $data = \Pimcore\Tool\Serialize::serialize($data);
        }

        $saveData = [
            "cid" => $this->model->getCid(),
            "ctype" => $this->model->getCtype(),
            "cpath" => $this->model->getCpath(),
            "name" => $this->model->getName(),
            "type" => $this->model->getType(),
            "inheritable" => (int)$this->model->getInheritable(),
            "data" => $data
        ];

        $this->db->insertOrUpdate("properties", $saveData);
    }
}
