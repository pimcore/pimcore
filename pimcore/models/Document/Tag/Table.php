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

class Document_Tag_Table extends Document_Tag {

    /**
     * Contains the text for this element
     *
     * @var array
     */
    public $data;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "table";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {

        $html = "";

        if (is_array($this->data) && count($this->data) > 0) {
            $html .= '<table border="0" cellpadding="0" cellspacing="0">';

            foreach ($this->data as $row) {
                $html .= '<tr>';
                foreach ($row as $col) {
                    $html .= '<td>';
                    $html .= $col;
                    $html .= '</td>';
                }
                $html .= '<tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->data = Pimcore_Tool_Serialize::unserialize($data);
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->data = $data;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->data);
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
        if ($data->data === null or is_array($data->data)) {
            $this->data = $data->data;
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }

    }

}
