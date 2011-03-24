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

class Object_Fieldcollection extends Pimcore_Model_Abstract implements Iterator {
    
    public $items = array();
    public $fieldname;
    
    public function __construct ($items = array(), $fieldname = null) {
        if(!empty($items)) {
            $this->setItems($items);
        }
        if($fieldname) {
            $this->setFieldname($fieldname);
        }
    }
    
    public function getItems () {
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
        
        $this->getResource()->save($object);
        $allowedTypes = $object->getClass()->getFieldDefinition($this->getFieldname())->getAllowedTypes();
        
        if(is_array($this->getItems())) {
            $index = 0;
            foreach ($this->getItems() as $collection) {
                if($collection instanceof Object_Fieldcollection_Data_Abstract) {
                    if(in_array($collection->getType(),$allowedTypes)) {
                        $collection->setFieldname($this->getFieldname());
                        $collection->setIndex($index++);
                        $collection->save($object);
                    } else {
                        throw new Exception("Fieldcollection of type " . $collection->getType() . " is not allowed in field: " . $this->getFieldname());
                    }
                }
            }
        }
    }
    
    
    public function isEmpty () {
        if(count($this->getItems()) < 1) {
            return true;
        }
        return false;
    }
    
    public function add ($item) {
        $this->items[] = $item;
    }
    
    public function remove ($index) {
        if($this->items[$index]) {
            array_splice($this->items,$index,1);
        }
    }
    
    public function get ($index) {
        if($this->items[$index]) {
            return $this->items[$index];
        }
    }
    
    public function getCount() {
        return count($this->getItems());
    }
    
    
    /**
     * Methods for Iterator
     */

    public function rewind() {
        reset($this->items);
    }

    public function current() {
        $var = current($this->items);
        return $var;
    }

    public function key() {
        $var = key($this->items);
        return $var;
    }

    public function next() {
        $var = next($this->items);
        return $var;
    }

    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }
}
