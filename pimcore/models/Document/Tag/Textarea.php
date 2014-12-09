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

class Textarea extends Model\Document\Tag {

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
        return "textarea";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return $this->text;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
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
     *
     */
    public function getDataEditmode() {
        return htmlentities($this->text);
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
        $data = html_entity_decode($data, ENT_HTML5); // this is because the input is now an div contenteditable -> therefore in entities
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

}
