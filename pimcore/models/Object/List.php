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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_List extends Pimcore_Model_List_Abstract implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    /**
     * @var array
     */
    public $objects = null;
    
    /**
     * @var boolean
     */
    public $unpublished = false;


    public $objectTypes = array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER);
    
    /**
     * @var array
     */
    public $validOrderKeys = array(
        "o_creationDate",
        "o_modificationDate",
        "o_id",
        "o_key",
        "o_index"
    );

    /**
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
        //TODO: ???
        /*if(in_array($key,$this->validOrderKeys)) {
              return true;
          }
          return false;*/
    }

    public function getCondition() {
        $condition = parent::getCondition();

        if(!empty($this->objectTypes)) {
            if(!empty($condition)) {
                $condition .= " AND ";
            }
            $condition .= " o_type IN ('" . implode("','", $this->objectTypes) . "')";
        }

        return $condition;
    }

    /**
     * @return array
     */
    public function getObjects() {
        if ($this->objects === null) {
            $this->load();
        }
        return $this->objects;
    }

    /**
     * @param string $objects
     * @return void
     */
    public function setObjects($objects) {
        $this->objects = $objects;
    }
    
    /**
     * @return bool
     */
    public function getUnpublished() {
        return $this->unpublished;
    }
    
    /**
     * @return bool
     */
    public function setUnpublished($unpublished) {
        $this->unpublished = (bool) $unpublished;
    }

    /**
     * @param  $objectTypes
     * @return void
     */
    public function setObjectTypes($objectTypes) {
        $this->objectTypes = $objectTypes;
    }

    /**
     * @return array
     */
    public function getObjectTypes() {
        return $this->objectTypes;
    }
    

    /**
     *
     * Methods for Zend_Paginator_Adapter_Interface
     */

    public function count() {
        return parent::getTotalCount();
    }

    public function getItems($offset, $itemCountPerPage) {
        parent::setOffset($offset);
        parent::setLimit($itemCountPerPage);
        return parent::load();
    }

    public function getPaginatorAdapter() {
        return $this;
    }

    /**
     * Methods for Iterator
     */

    public function rewind() {
        $this->getObjects();
        reset($this->objects);
    }

    public function current() {
        $this->getObjects();
        $var = current($this->objects);
        return $var;
    }

    public function key() {
        $this->getObjects();
        $var = key($this->objects);
        return $var;
    }

    public function next() {
        $this->getObjects();
        $var = next($this->objects);
        return $var;
    }

    public function valid() {
        $this->getObjects();
        $var = $this->current() !== false;
        return $var;
    }
}
