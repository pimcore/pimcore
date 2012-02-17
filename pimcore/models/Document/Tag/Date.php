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

class Document_Tag_Date extends Document_Tag {

    /**
     * Contains the date
     *
     * @var Zend_Date
     */
    public $date;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "date";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->date;
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return string
     */
    public function getDataEditmode() {
        if ($this->date instanceof Zend_Date) {
            return $this->date->get(Zend_Date::TIMESTAMP);
        }
        $date = new Zend_Date();
        $date->get(Zend_Date::TIMESTAMP);
    }

    /**
     * @see Document_Tag_Interface::frontend
     */
    public function frontend() {

        Pimcore_Date::setOptions(array('format_type' => 'php'));
        if (!$this->options["output"]) {
            $this->options["output"] = Zend_Date::DATE_MEDIUM;
        }

        if ($this->date instanceof Zend_Date) {
            return $this->date->toString($this->options["format"]);
        }

    }
    
    /**
     * @see Document_Tag::getDataForResource
     * @return void
     */
    public function getDataForResource () {
        $this->checkValidity();
        if($this->date instanceof Zend_Date) {
            return $this->date->get(Zend_Date::TIMESTAMP);
        }
        return;
    }
    
    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if($data) {
            $this->date = new Pimcore_Date($data);
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        if (strlen($data) > 5) {
            // ext 2.0 returns the selected date in UTC
            date_default_timezone_set("UTC");

            $this->date = new Pimcore_Date($data, Zend_Date::ISO_8601);
            $this->date->setTimezone(Pimcore_Config::getSystemConfig()->general->timezone);

            // set default timezone
            date_default_timezone_set(Pimcore_Config::getSystemConfig()->general->timezone);
        }
    }
    
    /**
     * @return boolean
     */
    public function isEmpty () {
        if($this->date instanceof Zend_Date) {
            return false;
        }
        return true;
    }


        /**
        * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
        *
        * @abstract
        * @param  Webservice_Data_Document_Element $data
        * @return void
        */
       public function getFromWebserviceImport($wsElement){

           if(!$wsElement or empty($wsElement->value)){
                $this->date=null;
           }else if(is_numeric($wsElement->value)){
               $this->date = new Pimcore_Date($wsElement->value);
           } else {
               throw new Exception("cannot get document tag date from WS - invalid value [  ".$wsElement->value." ]");
           }


       }



    /**
     * Returns the current tag's data for web service export
     *
     * @abstract
     * @return array
     */
    public function getForWebserviceExport() {
       if($this->date){
           return $this->date->getTimestamp();
       } else return null;
    }

}
