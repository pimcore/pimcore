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

class Document_Tag_Multiselect extends Document_Tag {

    /**
     * Contains the current selected values
     *
     * @var array
     */
    public $values = array();

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "multiselect";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->values;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        return implode(",", $this->values);
    }

    public function getDataEditmode() {
        return implode(",", $this->values);
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->values = Pimcore_Tool_Serialize::unserialize($data);
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->values = empty($data)?array():explode(",", $data);
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->values);
    }


    /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement) {
        $data = $wsElement->value;
        if ($data->values === null or is_array($data->values)) {
            $this->values = $data->values;
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }

    }

}
