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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Classificationstore\CollectionGroupRelation;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    const TABLE_NAME_RELATIONS = "classificationstore_collectionrelations";


    public function getById($colId = null, $groupId = null) {

        if ($colId != null) {
            $this->model->setColId($colId);
        }

        if ($groupId != null) {
            $this->model->setGroupId($groupId);
        }


        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_RELATIONS
            . "," . Model\Object\Classificationstore\GroupConfig\Resource::TABLE_NAME_GROUPS. " WHERE colId = ? AND groupId = `?"
            , $this->model->getColId(), $this->model->groupId);

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        return $this->update();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::TABLE_NAME_RELATIONS,
            $this->db->quoteInto("colId = ? AND ", $this->model->getColId())
            . $this->db->quoteInto("groupId = ? ",$this->model->getGroupId()));

    }

    /**
     * @throws \Exception
     */
    public function update() {
        try {
            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_RELATIONS))) {
                    if(is_bool($value)) {
                        $value = (int) $value;
                    }
                    if(is_array($value) || is_object($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->insertOrUpdate(self::TABLE_NAME_RELATIONS, $data);
            return $this->model;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert(self::TABLE_NAME_RELATIONS, array());
        return $this->save();
    }
}
