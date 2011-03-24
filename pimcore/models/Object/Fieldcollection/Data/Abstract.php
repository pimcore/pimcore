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

class Object_Fieldcollection_Data_Abstract extends Pimcore_Model_Abstract {
    
    public $index;
    public $fieldname;    
    
    public function getIndex () {
        return $this->index;
    }
    
    public function setIndex ($index) {
        $this->index = (int) $index;
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
        $definition = Object_Fieldcollection_Definition::getByKey($this->getType());
        return $definition;
    }
}
