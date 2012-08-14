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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Property_Predefined_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of predefined properties for the specicifies parameters, returns an array of Property_Predefined elements
     *
     * @return array
     */
    public function load() {

        $properties = array();
        $propertiesData = $this->db->fetchCol("SELECT id FROM properties_predefined" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($propertiesData as $propertyData) {
            $properties[] = Property_Predefined::getById($propertyData);
        }

        $this->model->setProperties($properties);
        return $properties;
    }

    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM properties_predefined " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }

        return $amount;
    }

}
