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

class Object_Data_ObjectMetadata extends Pimcore_Model_Abstract {

    /**
     * @var Object_Concrete
     */
    protected $object;
    protected $fieldname;
    protected $columns = array();
    protected $data = array();

    public function __construct($fieldname, $columns = array(), $object = null) {
        $this->fieldname = $fieldname;
        $this->object = $object;
        $this->columns = $columns;
    }

    public function __call($name, $arguments) {

        if(substr($name, 0, 3) == "get") {
            $key = strtolower(substr($name, 3, strlen($name)-3));

            if(in_array($key, $this->columns)) {
                return $this->data[$key];
            }

            throw new Exception("Requested data $key not available");
        }


        if(substr($name, 0, 3) == "set") {
            $key = strtolower(substr($name, 3, strlen($name)-3));
            if(in_array($key, $this->columns)) {
                $this->data[$key] = $arguments[0];
            } else {
                throw new Exception("Requested data $key not available");
            }

        }

    }

    public function save($object) {
        $this->getResource()->save($object);
    }

    public function load(Object_Concrete $source, $destination, $fieldname) {
        return $this->getResource()->load($source, $destination, $fieldname);
    }


    public function setFieldname($fieldname) {
        $this->fieldname = $fieldname;
    }

    public function getFieldname() {
        return $this->fieldname;
    }

    public function setObject($object) {
        $this->object = $object;
    }

    /**
     * @return Object_Concrete
     */
    public function getObject() {
        return $this->object;
    }

    public function setColumns($columns) {
        $this->columns = $columns;
    }

    public function getColumns() {
        return $this->columns;
    }


    public function __toString() {
        return $this->getObject()->__toString();
    }

}
