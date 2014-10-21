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
use Pimcore\Tool\Text; 

include_once("simple_html_dom.php");

class Wysiwyg extends Model\Document\Tag {

    /**
     * Contains the text
     *
     * @var string
     */
    public $text;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "wysiwyg";
    }

    /**
     * @see Document\Tag\TagInterface::getData
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
        return Text::wysiwygText($this->text);
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend() {
        return Text::wysiwygText($this->text);
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->text = $data;
        return $this;
    }


    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
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
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }
    
    /**
     * @return array
     */
    public function resolveDependencies() {
        return Text::getDependenciesOfWysiwygText($this->text);
    }


    /**
     * @param $ownerDocument
     * @param array $blockedTags
     * @return array
     */
    public function getCacheTags($ownerDocument, $blockedTags = array()) {
        return Text::getCacheTagsOfWysiwygText($this->text, $blockedTags);
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
