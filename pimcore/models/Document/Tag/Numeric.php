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

class Document_Tag_Numeric extends Document_Tag {

    /**
     * Contains the current number, or an empty string if not set
     *
     * @var string
     */
    public $number = "";


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "numeric";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->number;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        return $this->number;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->number = $data;
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->number = $data;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->number);
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
           if($data->number === null or is_numeric($data->number)){
                $this->number = $data->number;
           } else {
               throw new Exception("cannot get values from web service import - invalid data");
           }

       }
}
