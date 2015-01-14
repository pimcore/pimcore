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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\TmpStore;

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
        $this->validColumns = $this->getValidTableColumns("tmp_store");
    }

    /**
     * @param $id
     * @param $data
     * @param $tag
     * @param $lifetime
     * @return bool
     */
    public function add ($id, $data, $tag, $lifetime) {

        try {
            $serialized = false;
            if(is_object($data) || is_array($data)) {
                $serialized = true;
                $data = serialize($data);
            }

            $this->db->insertOrUpdate("tmp_store", [
                "id" => $id,
                "data" => $data,
                "tag" => $tag,
                "date" => time(),
                "expiryDate" => (time()+$lifetime),
                "serialized" => (int) $serialized
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $id
     */
    public function delete ($id) {
        $this->db->delete("tmp_store", "id = " . $this->db->quote($id));
    }

    /**
     * @param $id
     * @return bool
     */
    public function getById($id) {
        $item = $this->db->fetchRow("SELECT * FROM tmp_store WHERE id = ?", $id);

        if(array_key_exists("id", $item)) {

            if($item["serialized"]) {
                $item["data"] = unserialize($item["data"]);
            }

            $this->assignVariablesToModel($item);
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function cleanup() {
        $this->db->delete("tmp_store", "expiryDate < " . time());
    }

    /**
     * @param $tag
     * @return array
     */
    public function getIdsByTag($tag) {
        $items = $this->db->fetchCol("SELECT id FROM tmp_store WHERE tag = ?", [$tag]);
        return $items;
    }
}
