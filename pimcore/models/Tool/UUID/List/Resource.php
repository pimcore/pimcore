<?php
 /* Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_UUID_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     * @return array
     */
    public function load() {
        $items = $this->db->fetchCol("SELECT uuid FROM " . Tool_UUID_Resource::TABLE_NAME ." ". $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        $result = array();
        foreach ($items as $uuid) {
            $result[] = Tool_UUID::getByUuid($uuid);
        }

        return $result;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     * @return integer
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . Tool_UUID_Resource::TABLE_NAME ." " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }
        return $amount;
    }
}
