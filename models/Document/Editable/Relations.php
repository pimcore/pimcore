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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Relations extends Model\Document\Editable implements \Iterator
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
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'relations';
    }

    /**
     * @return $this
     */
    public function setElements()
    {
        if (empty($this->elements)) {
            $this->elements = [];
            foreach ($this->elementIds as $elementId) {
                $el = Element\Service::getElementById($elementId['type'], $elementId['id']);
                if ($el instanceof Element\ElementInterface) {
                    $this->elements[] = $el;
                }
            }
        }

        return $this;
    }

    /**
     * @see EditableInterface::getData
     *
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
     *
     * @return array
     */
    public function getDataEditmode()
    {
        $this->setElements();
        $return = [];

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof DataObject\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'object', $element->getClassName()];
                } elseif ($element instanceof DataObject\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'object', 'folder'];
                } elseif ($element instanceof Asset) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'asset', $element->getType()];
                } elseif ($element instanceof Document) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'document', $element->getType()];
                }
            }
        }

        return $return;
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $this->setElements();
        $return = '';

        foreach ($this->getElements() as $element) {
            if ($element instanceof Element\ElementInterface) {
                $return .= Element\Service::getElementType($element) . ': ' . $element->getFullPath() . '<br />';
            }
        }

        return $return;
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param mixed $data
     *
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
     * @see EditableInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        if (is_array($data)) {
            $this->elementIds = $data;
            $this->elements = [];
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
            if (Element\Service::doHideUnpublished($element)) {
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
     * @return bool
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
                    $key = $elementType . '_' . $element->getId();

                    $dependencies[$key] = [
                        'id' => $element->getId(),
                        'type' => $elementType,
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
     *
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        // reset existing elements store
        $this->elements = [];

        foreach ($this->elementIds as &$elementId) {
            $type = $elementId['type'];
            $id = $elementId['id'];

            if (array_key_exists($type, $idMapping) && array_key_exists((int) $id, $idMapping[$type])) {
                $elementId['id'] = $idMapping[$type][$id];
            }
        }

        $this->setElements();
    }

    /**
     * @deprecated
     *
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $wsData = $wsElement->value;
        if (is_array($wsData)) {
            $result = [];
            foreach ($wsData as $data) {
                $data = $this->sanitizeWebserviceData($data);
                if ($data->id !== null) {
                    $resultItem = [];
                    $resultItem['type'] = $data->type;

                    if (!is_numeric($data->id)) {
                        throw new \Exception('cannot get values from web service import - id is not valid');
                    }

                    if ($idMapper) {
                        $data->id = $idMapper->getMappedId($data->type, $data->id);
                    }
                    $resultItem['id'] = $data->id;

                    if ($data->type == 'asset') {
                        $element = Asset::getById($data->id);
                        if (!$element instanceof Asset) {
                            if ($idMapper && $idMapper->ignoreMappingFailures()) {
                                $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                            } else {
                                throw new \Exception('cannot get values from web service import - referenced asset with id [ ' . $data->id . ' ] is unknown');
                            }
                        }
                    } elseif ($data->type == 'document') {
                        $element = Document::getById($data->id);
                        if (!$element instanceof Document) {
                            if ($idMapper && $idMapper->ignoreMappingFailures()) {
                                $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                            } else {
                                throw new \Exception('cannot get values from web service import - referenced document with id [ ' . $data->id . ' ] is unknown');
                            }
                        }
                    } elseif ($data->type == 'object') {
                        $element = DataObject\AbstractObject::getById($data->id);
                        if (!$element instanceof DataObject\AbstractObject) {
                            if ($idMapper && $idMapper->ignoreMappingFailures()) {
                                $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                            } else {
                                throw new \Exception('cannot get values from web service import - referenced object with id [ ' . $data->id . ' ] is unknown');
                            }
                        }
                    } else {
                        if ($idMapper && $idMapper->ignoreMappingFailures()) {
                            $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                        } else {
                            throw new \Exception('cannot get values from web service import - type is not valid');
                        }
                    }
                    $result[] = $resultItem;
                }
            }
            $this->elementIds = $result;
        }
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['elements'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

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

        $el = $this->current();
        if (Element\Service::doHideUnpublished($el)) {
            if (!Element\Service::isPublished($el)) {
                $this->next();
            }
        }

        $var = $this->current() !== false;

        return $var;
    }

    /**
     * Returns the current tag's data for web service export
     *
     * @deprecated
     *
     * @param Model\Document\PageSnippet|null $document
     * @param array $params
     *
     * @return array|null
     */
    public function getForWebserviceExport($document = null, $params = [])
    {
        $elements = $this->getElements();
        if (is_array($elements)) {
            $result = [];
            foreach ($elements as $element) {
                $result[] = [
                    'type' => Element\Service::getType($element),
                    'id' => $element->getId(),
                ];
            }

            return $result;
        }

        return null;
    }
}

class_alias(Relations::class, 'Pimcore\Model\Document\Tag\Multihref');
class_alias(Relations::class, 'Pimcore\Model\Document\Tag\Relations');
