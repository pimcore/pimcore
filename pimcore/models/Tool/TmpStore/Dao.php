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
 * @package    Tool
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\TmpStore;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\TmpStore $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $id
     * @param $data
     * @param $tag
     * @param $lifetime
     * @return bool
     */
    public function add($id, $data, $tag, $lifetime)
    {
        try {
            $serialized = false;
            if (is_object($data) || is_array($data)) {
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
    public function delete($id)
    {
        $this->db->delete("tmp_store", ["id" => $id]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function getById($id)
    {
        $item = $this->db->fetchRow("SELECT * FROM tmp_store WHERE id = ?", $id);

        if (is_array($item) && array_key_exists("id", $item)) {
            if ($item["serialized"]) {
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
    public function cleanup()
    {
        $this->db->deleteWhere("tmp_store", "expiryDate < " . time());
    }

    /**
     * @param $tag
     * @return array
     */
    public function getIdsByTag($tag)
    {
        $items = $this->db->fetchCol("SELECT id FROM tmp_store WHERE tag = ?", [$tag]);

        return $items;
    }
}
