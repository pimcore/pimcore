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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Tag_Checkbox extends Document_Tag {

    /**
     * Contains the checkbox value
     *
     * @var boolean
     */
    public $value = false;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "checkbox";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->value;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        return $this->value;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->value = $data;
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->value = $data;
    }


    /**
     * @return boolean
     */
    public function isEmpty() {
        return $this->value;
    }

    /**
     * @return boolean
     */
    public function isChecked() {
        return $this->isEmpty();
    }


    /**
        * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
        *
        * @abstract
        * @param  Webservice_Data_Document_Element $data
        * @return void
        */
       public function getFromWebserviceImport($wsElement){
           $data = $wsElement->value;
           if($data->bool === null or is_bool($data)){
                $this->value = (bool) $data->value;
           } else {
               throw new Exception("cannot get values from web service import - invalid data");
           }

       }


}
