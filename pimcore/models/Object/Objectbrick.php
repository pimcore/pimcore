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
 * @package    Object_Objectbrick
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick extends Pimcore_Model_Abstract {

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var Object_Concrete
     */
    public $object;

    /**
     * @var array
     */
    protected $brickGetters = array();

    /**
     * @param Object_Concrete $object
     * @param string $fieldname
     */
    public function __construct ($object, $fieldname) {
        $this->setObject($object);
        if($fieldname) {
            $this->setFieldname($fieldname);
        }
    }

    /**
     * @return array
     */
    public function getItems ($withInheritedValues = false) {
        if($withInheritedValues) {
            $getters = $this->getBrickGetters();
            $values = array();
            foreach($getters as $getter) {
                $value = $this->$getter();
                if(!empty($value)) {
                    $values[] = $value;
                }
            }

            return $values;
        } else {
            if(empty($this->items)) {
                foreach(get_object_vars($this) as $var) {
                    if($var instanceof Object_Objectbrick_Data_Abstract) {
                        $this->items[] = $var;
                    }
                }
            }
            return $this->items;
        }
    }

    /**
     * @param $items
     * @return void
     */
    public function setItems ($items) {
        $this->items = $items;
    }

    /**
     * @return string
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
    }

    /**
     * @return array
     */
    public function getBrickGetters() {
        $getters = array();
        foreach($this->brickGetters as $bg) {
            $getters[] = "get" . ucfirst($bg);
        }
        return $getters;
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
     * @param Object_Concrete $object
     * @return void
     */
    public function save ($object) {

        $getters = $this->getBrickGetters();

        foreach($getters as $getter) {
            $brick = $this->$getter();

            if($brick instanceof Object_Objectbrick_Data_Abstract) {
                if($brick->getDoDelete()) {
                    $brick->delete($object);

                    $setter = "s" . substr($getter, 1);
                    $this->$setter(null);

                    //check if parent object has brick, and if so, create an empty brick to enable inheritance
                    $parentBrick = null;
                    $inheritanceModeBackup = Object_Abstract::getGetInheritedValues();
                    Object_Abstract::setGetInheritedValues(true);
                    if(Object_Abstract::doGetInheritedValues($object)) {
                        $container = $object->getValueFromParent($this->fieldname);
                        if(!empty($container)) {
                            $parentBrick = $container->$getter();
                        }
                    }
                    Object_Abstract::setGetInheritedValues($inheritanceModeBackup);

                    if(!empty($parentBrick)) {
                        $brickType = "Object_Objectbrick_Data_" . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object);
                        $this->$setter($brick);
                    }


                } else  {
                    $brick->setFieldname($this->getFieldname());
                    $brick->save($object);
                }

            } else {
                if($brick == null) {
                    $parentBrick = null;
                    $inheritanceModeBackup = Object_Abstract::getGetInheritedValues();
                    Object_Abstract::setGetInheritedValues(true);
                    if(Object_Abstract::doGetInheritedValues($object)) {
                        $container = $object->getValueFromParent($this->fieldname);
                        if(!empty($container)) {
                            $parentBrick = $container->$getter();
                        }
                    }
                    Object_Abstract::setGetInheritedValues($inheritanceModeBackup);

                    if(!empty($parentBrick)) {
                        $brickType = "Object_Objectbrick_Data_" . ucfirst($parentBrick->getType());
                        $brick = new $brickType($object);
                        $brick->setFieldname($this->getFieldname());
                        $brick->save($object);
                    }
                }
            }
        }
    }

    /**
     * @return Object_Concrete
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function setObject($object) {
        $this->object = $object;
    }

    /**
     * @param Object_Concrete $object
     * @return void 
     */
    public function delete(Object_Concrete $object) {
        if(is_array($this->getItems())) {
            foreach ($this->getItems() as $brick) {
                if($brick instanceof Object_Objectbrick_Data_Abstract) {
                    $brick->delete($object);
                }
            }
        }
    }
    
}
