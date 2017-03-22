<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Multihref extends Model\Document\Tag implements \Iterator
{

    /**
     * @var array
     */
    public $elements = [];

    /**
     * @var array
     */
    public $elementIds = [];

     /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "multihref";
    }

    /**
     * @return $this
     */
    public function setElements()
    {
        if (empty($this->elements)) {
            $this->elements = [];
            foreach ($this->elementIds as $elementId) {
                $el = Element\Service::getElementById($elementId["type"], $elementId["id"]);
                if ($el instanceof Element\ElementInterface) {
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
    public function getData()
    {
        $this->setElements();

        return $this->elements;
    }

    /**
     * @return array
     */
    public function getDataForResource()
    {
        return $this->elementIds;
    }

    /**
     * Converts the data so it's suitable for the editmode
     * @return mixed
     */
    public function getDataEditmode()
    {
        $this->setElements();
        $return = [];

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Object\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "object", $element->getClassName()];
                } elseif ($element instanceof Object\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "object", "folder"];
                } elseif ($element instanceof Asset) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "asset", $element->getType()];
                } elseif ($element instanceof Document) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "document", $element->getType()];
                }
            }
        }

        return $return;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        $this->setElements();
        $return = "";

        foreach ($this->getElements() as $element) {
            $return .= Element\Service::getElementType($element) . ": " . $element->getFullPath() . "<br />";
        }

        return $return;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if ($data = \Pimcore\Tool\Serialize::unserialize($data)) {
            $this->setDataFromEditmode($data);
        }

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        if (is_array($data)) {
            $this->elementIds = $data;
        }

        return $this;
    }

    /**
     * @return Element\ElementInterface[]
     */
    public function getElements()
    {
        $this->setElements();

        $elements = [];

        foreach ($this->elements as $element) {
            if (
                ($element instanceof Object && Object::doHideUnpublished())
                ||
                ($element instanceof Document && Document::doHideUnpublished())
            ) {
                if (Element\Service::isPublished($element)) {
                    $elements[] = $element;
                }
            } else {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        $this->setElements();

        return count($this->elements) > 0 ? false : true;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $this->setElements();
        $dependencies = [];

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $key = $elementType . "_" . $element->getId();

                    $dependencies[$key] = [
                        "id" => $element->getId(),
                        "type" => $elementType
                    ];
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
     */
    public function rewriteIds($idMapping)
    {
        // reset existing elements store
        $this->elements = [];

        foreach ($this->elementIds as &$elementId) {
            $type = $elementId["type"];
            $id = $elementId["id"];

            if (array_key_exists($type, $idMapping) and array_key_exists((int) $id, $idMapping[$type])) {
                $elementId["id"] = $idMapping[$type][$id];
            }
        }

        $this->setElements();
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param null $document
     * @param array $params
     * @param null $idMapper
     * @return array
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        // currently unsupported
        return [];
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ["elements"];
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
    public function load()
    {
        $this->setElements();
    }

    /**
     * Methods for Iterator
     */

    public function rewind()
    {
        $this->setElements();
        reset($this->elements);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->setElements();
        $var = current($this->elements);

        return $var;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->setElements();
        $var = key($this->elements);

        return $var;
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $this->setElements();
        $var = next($this->elements);

        return $var;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->setElements();
        $var = $this->current() !== false;

        return $var;
    }
}
