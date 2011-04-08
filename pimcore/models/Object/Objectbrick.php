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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick extends Pimcore_Model_Abstract {
    
    public $items = array();
    public $fieldname;

    private $object = null;

    public function __construct ($object, $fieldname = null) {
        $this->object = $object;
        if($fieldname) {
            $this->setFieldname($fieldname);
        }
    }
    
    public function getItems () {
        if(empty($this->items)) {
//            p_r(get_object_vars($this));die();

            foreach(get_object_vars($this) as $var) {
                if($var instanceof Object_Objectbrick_Data_Abstract) {
                    $this->items[] = $var;
                }
            }
        }
        return $this->items;
    }
    
    public function setItems ($items) {
        $this->items = $items;
    }
    
    public function getFieldname () {
        return $this->fieldname;
    }
    
    public function setFieldname ($fieldname) {
        $this->fieldname = $fieldname;
    }
    
    public function getItemDefinitions () {
        $definitions = array();
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }
        
        return $definitions;
    }
    
    public function save ($object) {
        
//        $this->getResource()->save($object);
//        $allowedTypes = $object->getClass()->getFieldDefinition($this->getFieldname())->getAllowedTypes();
        
        if(is_array($this->getItems())) {
            foreach ($this->getItems() as $brick) {
                if($brick instanceof Object_Objectbrick_Data_Abstract) {
                    if($brick->getDoDelete()) {
                        $brick->delete($object);
                    } else  {
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object);
                    }

                }
            }
        }
    }
    
    
//    public function isEmpty () {
//        if(count($this->getItems()) < 1) {
//            return true;
//        }
//        return false;
//    }
}
