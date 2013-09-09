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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Targeting_Persona_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of document-types for the specicifies parameters, returns an array of Document_DocType elements
     *
     * @return array
     */
    public function load() {

        $personasData = $this->db->fetchCol("SELECT id FROM targeting_personas" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $personas = array();
        foreach ($personasData as $personaData) {
            $personas[] = Tool_Targeting_Persona::getById($personaData);
        }

        $this->model->setPersonas($personas);
        return $personas;
    }

}
