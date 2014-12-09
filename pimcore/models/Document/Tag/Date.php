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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\Config;

class Date extends Model\Document\Tag {

    /**
     * Contains the date
     *
     * @var \Zend_Date
     */
    public $date;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "date";
    }

    /**
     * @see Document\Tag\TagInterface::getData
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
        if ($this->date instanceof \Zend_Date) {
            return $this->date->get(\Zend_Date::TIMESTAMP);
        }
        $date = new \Zend_Date();
        $date->get(\Zend_Date::TIMESTAMP);
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     */
    public function frontend() {

        if (!isset($this->options["output"]) || !$this->options["output"]) {
            $this->options["output"] = \Zend_Date::DATE_MEDIUM;
        }

        if ($this->date instanceof \Zend_Date) {
            return $this->date->toString($this->options["format"], "php");
        }
    }
    
    /**
     * @see Document\Tag::getDataForResource
     * @return void
     */
    public function getDataForResource () {
        $this->checkValidity();
        if($this->date instanceof \Zend_Date) {
            return $this->date->get(\Zend_Date::TIMESTAMP);
        }
        return;
    }
    
    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if($data) {
            $this->date = new \Pimcore\Date($data);
        }
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        if (strlen($data) > 5) {
            // ext 2.0 returns the selected date in UTC
            date_default_timezone_set("UTC");

            $this->date = new \Pimcore\Date($data, \Zend_Date::ISO_8601);
            $this->date->setTimezone(Config::getSystemConfig()->general->timezone);

            // set default timezone
            date_default_timezone_set(Config::getSystemConfig()->general->timezone);
        }
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function isEmpty () {
        if($this->date instanceof \Zend_Date) {
            return false;
        }
        return true;
    }

    /**
    * Receives a Webservice\Data\Document\Element from webservice import and fill the current tag's data
    *
    * @param  Model\Webservice\Data\Document\Element $wsElement
     * @param $idMapper
    * @throws \Exception
    */
    public function getFromWebserviceImport($wsElement, $idMapper = null){

       if(!$wsElement or empty($wsElement->value)){
            $this->date=null;
       }else if(is_numeric($wsElement->value)){
           $this->date = new \Pimcore\Date($wsElement->value);
       } else {
           throw new \Exception("cannot get document tag date from WS - invalid value [  ".$wsElement->value." ]");
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
