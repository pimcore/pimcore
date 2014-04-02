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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_CustomLayout_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of custom layouts for the specified parameters, returns an array of Object_Class_CustomLayout elements
     *
     * @return array
     */
    public function load() {

        $layouts = array();

        $layoutsRaw = $this->db->fetchCol("SELECT id FROM custom_layouts" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($layoutsRaw as $classRaw) {
            $layouts[] = Object_Class_CustomLayout::getById($classRaw);
        }

        $this->model->setLayoutDefinitions($layouts);

        return $layouts;
    }

    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM custom_layouts " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }

        return $amount;
    }
}
