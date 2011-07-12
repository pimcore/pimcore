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

class Object_Class_Data_Time extends Object_Class_Data_Input {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "time";

    /**
     * Column length
     *
     * @var integer
     */
    public $columnLength = 5;

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        parent::checkValidity($data, $omitMandatoryCheck);

        if((is_string($data) && strlen($data) != 5 && !empty($data)) || (!empty($data) && !is_string(§data))) {
            throw new Exception("Wrong time format given must be a 5 digit string (eg: 06:49) [ ".$this->getName()." ]");
        }
    }
}
