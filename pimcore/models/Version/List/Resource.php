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
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Version_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Schedule_Task elements
     *
     * @return array
     */
    public function load() {

        $versions = array();
        $data = $this->db->fetchCol("SELECT id FROM versions" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $id) {
            $versions[] = Version::getById($id);
        }

        $this->model->setVersions($versions);
        return $versions;
    }

    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM versions " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }

        return $amount;
    }
}
