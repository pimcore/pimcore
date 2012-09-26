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


    /**
     * @var array
     */
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

    /**
     * @return int
     */
    public function count() {
        return $this->getTotalCount();
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     * @return array
     */
    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
        return $this->load();
    }

    /**
     * @return Object_List|Zend_Paginator_Adapter_Interface
     */
    public function getPaginatorAdapter() {
        return $this;
    }

    /**
     * Methods for Iterator
     */

    /**
     *
     */
    public function rewind() {
        $this->getObjects();
        reset($this->objects);
    }

    /**
     * @return mixed
     */
    public function current() {
        $this->getObjects();
        $var = current($this->objects);
        return $var;
    }

    /**
     * @return mixed
     */
    public function key() {
        $this->getObjects();
        $var = key($this->objects);
        return $var;
    }

    /**
     * @return mixed|void
     */
    public function next() {
        $this->getObjects();
        $var = next($this->objects);
        return $var;
    }

    /**
     * @return bool
     */
    public function valid() {
        $this->getObjects();
        $var = $this->current() !== false;
        return $var;
    }
}
