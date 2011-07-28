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

abstract class Object_Fieldcollection_Data_Abstract extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $index;

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var Object_Concrete
     */
    public $object;

    /**
     * @var string
     */
    public $type;

    /**
     * @return int
     */
    public function getIndex () {
        return $this->index;
    }

    /**
     * @param int $index
     * @return void
     */
    public function setIndex ($index) {
        $this->index = (int) $index;
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
     * @return string
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition () {
        $definition = Object_Fieldcollection_Definition::getByKey($this->getType());
        return $definition;
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
