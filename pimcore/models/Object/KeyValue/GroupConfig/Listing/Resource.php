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

namespace Pimcore\Model\Object\KeyValue\GroupConfig\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of keyvalue group configs for the specifies parameters, returns an array of config elements
     *
     * @return array
     */
    public function load() {
        $sql = "SELECT id FROM " . Object\KeyValue\GroupConfig\Resource::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchCol($sql,  $this->model->getConditionVariables());

        $configData = array();
        foreach ($configsData as $config) {
            $configData[] = Object\KeyValue\GroupConfig::getById($config);
        }

        $this->model->setList($configData);
        return $configData;
    }

    /**
     * @return array
     */
    public function getDataArray() {
        $configsData = $this->db->fetchAll("SELECT * FROM " . Object\KeyValue\GroupConfig\Resource::TABLE_NAME_GROUPS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $configsData;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Object\KeyValue\GroupConfig\Resource::TABLE_NAME_GROUPS . " ". $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }
}
