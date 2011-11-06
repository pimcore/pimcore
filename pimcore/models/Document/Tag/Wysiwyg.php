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
     * Rewrites id from source to target, $idMapping contains sourceId => targetId mapping
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
                if ($el->href) {
                    if ($el->pimcore_type == "document") {
                        if(array_key_exists( (int) $el->pimcore_id, $idMapping)) {
                            $el->outertext = str_replace('="' . $el->pimcore_id . '"', '="' . $idMapping[$el->pimcore_id] . '"', $el->outertext);
                        }
                    }
                }
            }
        }

        $this->text = $html->save();
    }
}
