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
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Permission_Definition_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of definitions for the specicified parameters, returns an array of User_Permission_Definition elements
     *
     * @return array
     */
    public function load() {

        $definitions = array();
        $definitionsData = $this->db->fetchAll("SELECT * FROM users_permission_definitions" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($definitionsData as $definitionData) {
            $definition = new User_Permission_Definition($definitionData);
            $definitions[] = $definition;
        }

        $this->model->setDefinitions($definitions);
        return $definitions;
    }

}
