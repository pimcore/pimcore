<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
class Relations extends Model\Document\Editable implements \Iterator, IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * @internal
     *
     * @var Element\ElementInterface[]
     */
    protected $elements = [];

    /**
     * @internal
     *
     * @var array
     */
    protected $elementIds = [];

    /**
     * {@inheritdoc}
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
     * @return array
     */
    public function getElementIds()
    {
        return $this->elementIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->setElements();

        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForResource()
    {
        return $this->elementIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataEditmode() /** : mixed */
    {
        $this->setElements();
        $return = [];

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof DataObject\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, $element->getClassName()];
                } elseif ($element instanceof DataObject\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        if ($data = \Pimcore\Tool\Serialize::unserialize($data)) {
            $this->setDataFromEditmode($data);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->setElements();

        return count($this->elements) > 0 ? false : true;
    }

    /**
     * {@inheritdoc}
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
     * { @inheritdoc }
     */
    public function rewriteIds($idMapping) /** : void */
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function load() /** : void */
    {
        $this->setElements();
    }

    /**
     * Methods for Iterator
     */

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()// : void
    {
        $this->setElements();
        reset($this->elements);
    }

    /**
     * @return Element\ElementInterface|false
     */
    #[\ReturnTypeWillChange]
    public function current()// : Element\ElementInterface|false
    {
        $this->setElements();

        return current($this->elements);
    }

    /**
     * @return int|null
     */
    #[\ReturnTypeWillChange]
    public function key()// : int|null
    {
        $this->setElements();

        return key($this->elements);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()// : void
    {
        $this->setElements();
        next($this->elements);
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()// : bool
    {
        $this->setElements();

        $el = $this->current();
        if (Element\Service::doHideUnpublished($el)) {
            if (!Element\Service::isPublished($el)) {
                $this->next();
            }
        }

        return $this->current() !== false;
    }
}
