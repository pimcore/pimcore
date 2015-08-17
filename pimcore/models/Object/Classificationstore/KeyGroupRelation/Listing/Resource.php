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

namespace Pimcore\Model\Object\Classificationstore\KeyGroupRelation\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of Classificationstore group configs for the specifies parameters, returns an array of config elements
     *
     * @return array
     */
    public function load()
    {
        $condition = $this->getCondition();
        if ($condition) {
            $condition = $condition . " AND ";
        } else {
            $condition = " where ";
        }
        $condition .= Object\Classificationstore\KeyGroupRelation\Resource::TABLE_NAME_RELATIONS
            . ".keyId = " . Object\Classificationstore\KeyConfig\Resource::TABLE_NAME_KEYS . ".id";

        $sql = "SELECT * FROM " . Object\Classificationstore\KeyGroupRelation\Resource::TABLE_NAME_RELATIONS
            . "," . Object\Classificationstore\KeyConfig\Resource::TABLE_NAME_KEYS
            . $condition . $this->getOrder() . $this->getOffsetLimit();
        $data = $this->db->fetchAll($sql, $this->model->getConditionVariables());

        $configData = array();
        foreach ($data as $dataItem) {
            $entry = new Object\Classificationstore\KeyGroupRelation();
            $resource = $entry->getResource();
            $resource->assignVariablesToModel($dataItem);

            $configData[] = $entry;
        }

        $this->model->setList($configData);
        return $configData;
    }

    /**
     * @return array
     */
    public function getDataArray() {
        $configsData = $this->db->fetchAll("SELECT * FROM " . Object\Classificationstore\KeyGroupRelation\Resource::TABLE_NAME_RELATIONS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $configsData;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Object\Classificationstore\KeyGroupRelation\Resource::TABLE_NAME_RELATIONS . " ". $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }
}
