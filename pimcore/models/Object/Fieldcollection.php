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
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;

class Fieldcollection extends Model\AbstractModel implements \Iterator {

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var
     */
    public $fieldname;

    /**
     * @param array $items
     * @param null $fieldname
     */
    public function __construct ($items = array(), $fieldname = null) {
        if(!empty($items)) {
            $this->setItems($items);
        }
        if($fieldname) {
            $this->setFieldname($fieldname);
        }
    }

    /**
     * @return array
     */
    public function getItems () {
        return $this->items;
    }

    /**
     * @param $items
     * @return void
     */
    public function setItems ($items) {
        $this->items = $items;
        return $this;
    }

    /**
     * @return
     */
    public function getFieldname () {
        return $this->fieldname;
    }

    /**
     * @param $fieldname
     * @return void
     */
    public function setFieldname ($fieldname) {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return array
     */
    public function getItemDefinitions () {
        $definitions = array();
        foreach ($this->getItems() as $item) {
            $definitions[$item->getType()] = $item->getDefinition();
        }
        
        return $definitions;
    }

    /**
     * @throws \Exception
     * @param $object
     * @return void
     */
    public function save ($object) {

        $this->getResource()->save($object);
        $allowedTypes = $object->getClass()->getFieldDefinition($this->getFieldname())->getAllowedTypes();

        if(is_array($this->getItems())) {
            $index = 0;
            foreach ($this->getItems() as $collection) {
                if($collection instanceof Fieldcollection\Data\AbstractData) {
                    if(in_array($collection->getType(),$allowedTypes)) {
                        $collection->setFieldname($this->getFieldname());
                        $collection->setIndex($index++);

                        // set the current object again, this is necessary because the related object in $this->object can change (eg. clone & copy & paste, etc.)
                        $collection->setObject($object);
                        $collection->save($object);
                    } else {
                        throw new \Exception("Fieldcollection of type " . $collection->getType() . " is not allowed in field: " . $this->getFieldname());
                    }
                }
            }
        }
    }
    
    /**
     * @return bool
     */
    public function isEmpty () {
        if(count($this->getItems()) < 1) {
            return true;
        }
        return false;
    }

    /**
     * @param $item
     * @return void
     */
    public function add ($item) {
        $this->items[] = $item;
    }

    /**
     * @param $index
     * @return void
     */
    public function remove ($index) {
        if($this->items[$index]) {
            array_splice($this->items,$index,1);
        }
    }

    /**
     * @param $index
     * @return 
     */
    public function get ($index) {
        if($this->items[$index]) {
            return $this->items[$index];
        }
    }

    /**
     * @return int
     */
    public function getCount() {
        return count($this->getItems());
    }
    
    
    /**
     * Methods for Iterator
     */


    /*
     *
     */
    public function rewind() {
        reset($this->items);
    }

    /**
     * @return mixed
     */
    public function current() {
        $var = current($this->items);
        return $var;
    }

    /**
     * @return mixed
     */
    public function key() {
        $var = key($this->items);
        return $var;
    }

    /**
     * @return mixed
     */
    public function next() {
        $var = next($this->items);
        return $var;
    }

    /**
     * @return bool
     */
    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }
}
