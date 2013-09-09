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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
include_once("simple_html_dom.php");

class Document_Tag_Wysiwyg extends Document_Tag {

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
        return "wysiwyg";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->text;
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode() {
        return Pimcore_Tool_Text::wysiwygText($this->text);
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        return Pimcore_Tool_Text::wysiwygText($this->text);
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->text = $data;
        return $this;
    }


    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->text = $data;
        return $this;
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
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
    }
    
    /**
     * @return array
     */
    public function resolveDependencies() {
        return Pimcore_Tool_Text::getDependenciesOfWysiwygText($this->text);
    }
    
    
    /**
     * @param $ownerDocument
     * @param array $blockedTags
     */
    public function getCacheTags($ownerDocument, $blockedTags = array()) {
        return Pimcore_Tool_Text::getCacheTagsOfWysiwygText($this->text, $blockedTags);
    }


    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param array $idMapping
     * @return void
     */
    public function rewriteIds($idMapping) {
        
        $html = str_get_html($this->text);
        if(!$html) {
            return $this->text;
        }

        $s = $html->find("a[pimcore_id],img[pimcore_id]");

        if($s) {
            foreach ($s as $el) {
                if ($el->href || $el->src) {
                    $type = $el->pimcore_type;
                    $id = (int) $el->pimcore_id;

                    if(array_key_exists($type, $idMapping)) {
                        if(array_key_exists($id, $idMapping[$type])) {
                            $el->outertext = str_replace('="' . $el->pimcore_id . '"', '="' . $idMapping[$type][$id] . '"', $el->outertext);
                        }
                    }
                }
            }
        }

        $this->text = $html->save();

        $html->clear();
        unset($html);
    }
}
