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

class Object_Class_Data_Personamultiselect extends Object_Class_Data_Multiselect {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "personamultiselect";


    /**
     *
     */
    public function configureOptions() {

        $list = new Tool_Targeting_Persona_List();
        $list->setOrder("asc");
        $list->setOrderKey("name");
        $personas = $list->load();

        $options = array();
        foreach ($personas as $persona) {
            $options[] = array(
                "value" => $persona->getId(),
                "key" => $persona->getName()
            );
        }

        $this->setOptions($options);
    }

    public function __wakeup() {
        $options = $this->getOptions();
        if(Pimcore::inAdmin() || empty($options)) {
            $this->configureOptions();
        }
    }
}
