<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Metadata;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {


    public function getRawData(){
        $cid = $this->model->getCid();
        $type = $this->model->getType();
        $name = $this->model->getName();
        $raw = null;
        if($cid){
            $data = $this->db->fetchRow("SELECT * FROM assets_metadata_predefined WHERE type=? AND cid = ? AND name=?",array($type,$cid,$name) );
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

            if ($data instanceof Model\Element\ElementInterface) {
                $data = $data->getId();
            }
            else {
                $data = null;
            }
        }


        if (is_array($data) || is_object($data)) {
            $data = \Pimcore\Tool\Serialize::serialize($data);
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

        $this->db->insertOrUpdate("properties", $saveData);
    }
}
