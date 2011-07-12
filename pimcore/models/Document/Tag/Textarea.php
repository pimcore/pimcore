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

class Document_Tag_Textarea extends Document_Tag {

    /**
     * Contains the text
     *
     * @var string
     */
    public $text;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "textarea";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->text;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {

        $options = $this->getOptions();

        $text = $this->text;
        if ($options["htmlspecialchars"] !== false) {
            $text = htmlspecialchars($this->text);
        }

        if ($options["nl2br"]) {
            $text = nl2br($text);
        }

        return $text;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->text = $data;
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->text = $data;
    }


    /**
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->text);
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
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }

    }

}
