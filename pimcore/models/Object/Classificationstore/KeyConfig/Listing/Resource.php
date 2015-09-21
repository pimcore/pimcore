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

namespace Pimcore\Model\Object\Classificationstore\KeyConfig\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of Classificationstore key configs for the specifies parameters, returns an array of config elements
     *
     * @return array
     */
    public function load() {
        $sql = "SELECT id FROM " . Object\Classificationstore\KeyConfig\Resource::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $configsData = $this->db->fetchCol($sql,  $this->model->getConditionVariables());

        $configData = array();
        foreach ($configsData as $config) {
            $configData[] = Object\Classificationstore\KeyConfig::getById($config);
        }

        $this->model->setList($configData);
        return $configData;
    }

    /**
     * @return array
     */
    public function getDataArray() {
        $configsData = $this->db->fetchAll("SELECT * FROM " . Object\Classificationstore\KeyConfig\Resource::TABLE_NAME_KEYS . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $configsData;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Object\Classificationstore\KeyConfig\Resource::TABLE_NAME_KEYS . " ". $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

    /**
     * @return string
     */
    protected function getCondition() {
        $condition = $this->model->getIncludeDisabled() ? "enabled is null or enabled is 0" : "enabled = 1";

        $cond = $this->model->getCondition();
        if ($cond) {
            $condition = $condition . " AND (" . $cond . ")";
        }

        if ($condition) {
            return " WHERE " . $condition . " ";
        }
        return "";
    }
}
