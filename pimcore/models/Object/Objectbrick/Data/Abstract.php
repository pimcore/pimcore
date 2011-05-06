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
 * @package    Object_Fieldcollection
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Data_Abstract extends Pimcore_Model_Abstract {
    
    public $fieldname;
    public $doDelete;
    protected $__object;

    public function __construct(Object_Concrete $object) {
        $this->__object = $object;
    }
    
    public function getFieldname () {
        return $this->fieldname;
    }
    
    public function setFieldname ($fieldname) {
        $this->fieldname = $fieldname;
    }
    
    public function getType () {
        return $this->type;
    }
    
    public function getDefinition () {
        $definition = Object_Objectbrick_Definition::getByKey($this->getType());
        return $definition;
    }

    public function setDoDelete($doDelete)
    {
        $this->doDelete = $doDelete;
    }

    public function getDoDelete()
    {
        return $this->doDelete;
    }

    public function getBaseObject() {
        return $this->__object;
    }

    public function delete($object) {
        $this->doDelete = true;
        parent::delete($object);
    }

    /**
     * @return mixed
     */
    public function getValueFromParent($key) {

        $parent = Object_Service::hasInheritableParentObject($this->getBaseObject());

        if(!empty($parent)) {
            $containerGetter = "get" . ucfirst($this->fieldname);
            $brickGetter = "get" . ucfirst($this->getType());
            $getter = "get" . ucfirst($key);

            if($parent->$containerGetter()->$brickGetter()) {
                return $parent->$containerGetter()->$brickGetter()->$getter();
            }

        }

        return null;
    }


}
