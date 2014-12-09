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
use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;

class Multihref extends Model\Document\Tag implements \Iterator {

    /**
     * @var array
     */
    public $elements = array();

    /**
     * @var array
     */
    public $elementIds = array();

     /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "multihref";
    }

    /*
     *
     */
    public function setElements() {
        if(empty($this->elements)) {
            $this->elements = array();
            foreach ($this->elementIds as $elementId) {
                $el = Element\Service::getElementById($elementId["type"], $elementId["id"]);
                if($el instanceof Element\ElementInterface) {
                    $this->elements[] = $el;
                }
            }
        }
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        $this->setElements();
        return $this->elements;
    }

    /**
     * @see Document\Tag\TagInterface::getDataForResource
     * @return void
     */
    public function getDataForResource() {
        return $this->elementIds;
    }

    /**
     * Converts the data so it's suitable for the editmode
     * @return mixed
     */
    public function getDataEditmode() {

        $this->setElements();
        $return = array();

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Object\Concrete) {
                    $return[] = array($element->getId(), $element->getFullPath(), "object", $element->getClassName());
                }
                else if ($element instanceof Object\AbstractObject) {
                    $return[] = array($element->getId(), $element->getFullPath(), "object", "folder");
                }
                else if ($element instanceof Asset) {
                    $return[] = array($element->getId(), $element->getFullPath(), "asset", $element->getType());
                }
                else if ($element instanceof Document) {
                    $return[] = array($element->getId(), $element->getFullPath(), "document", $element->getType());
                }
            }
        }

        return $return;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return void
     */
    public function frontend() {

        $this->setElements();
        $return = "";

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                $return .= Element\Service::getElementType($element) . ": " . $element->getFullPath() . "<br />";
            }
        }

        return $return;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if($data = \Pimcore\Tool\Serialize::unserialize($data)) {
            $this->setDataFromEditmode($data);
        }
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        if(is_array($data)) {
            $this->elementIds = $data;
        }
        return $this;
    }

    /**
     * @return Element\ElementInterface[]
     */
    public function getElements() {
        $this->setElements();
        return $this->elements;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        $this->setElements();
        return count($this->elements) > 0 ? false : true;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $this->setElements();
        $dependencies = array();

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Element\ElementInterface) {

                    $elementType = Element\Service::getElementType($element);
                    $key = $elementType . "_" . $element->getId();

                    $dependencies[$key] = array(
                        "id" => $element->getId(),
                        "type" => $elementType
                    );
                }
            }
        }

        return $dependencies;
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
        // reset existing elements store
        $this->elements = array();

        foreach ($this->elementIds as &$elementId) {

            $type = $elementId["type"];
            $id = $elementId["id"];

            if(array_key_exists($type, $idMapping) and array_key_exists((int) $id, $idMapping[$type])) {
                $elementId["id"] = $idMapping[$type][$id];
            }
        }

        $this->setElements();
    }

    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        // currently unsupported
        return array();
    }


    /**
     * @return array
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();
        $blockedVars = array("elements");
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     *
     */
    public function load () {
        $this->setElements();
    }

    /**
     * Methods for Iterator
     */

    public function rewind() {
        $this->setElements();
        reset($this->elements);
    }

    public function current() {
        $this->setElements();
        $var = current($this->elements);
        return $var;
    }

    public function key() {
        $this->setElements();
        $var = key($this->elements);
        return $var;
    }

    public function next() {
        $this->setElements();
        $var = next($this->elements);
        return $var;
    }

    public function valid() {
        $this->setElements();
        $var = $this->current() !== false;
        return $var;
    }
}
