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

class Object_Objectbrick_Data_Abstract extends Pimcore_Model_Abstract {

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var bool
     */
    public $doDelete;

    /**
     * @var Object_Concrete
     */
    public $object;


    /**
     * @param Object_Concrete $object
     */
    public function __construct(Object_Concrete $object) {
        $this->setObject($object);
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
     * @return 
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition () {
        $definition = Object_Objectbrick_Definition::getByKey($this->getType());
        return $definition;
    }

    /**
     * @param $doDelete
     * @return void
     */
    public function setDoDelete($doDelete)
    {
        $this->doDelete = $doDelete;
    }

    /**
     * @return bool
     */
    public function getDoDelete()
    {
        return $this->doDelete;
    }

    /**
     * for compatibility, in case of removeal, please force a save on every available brick in updatescript
     * @deprecated
     * @return
     */
    public function getBaseObject() {
        return $this->getObject();
    }

    /**
     * @param $object
     * @return void
     */
    public function delete($object) {
        $this->doDelete = true;
        parent::delete($object);
    }

    /**
     * @return mixed
     */
    public function getValueFromParent($key) {

        $parent = Object_Service::hasInheritableParentObject($this->getObject());

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

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return Object_Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

}
