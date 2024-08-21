<?php
declare(strict_types=1);

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

use Iterator;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Relations extends Model\Document\Editable implements Iterator, IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * @internal
     *
     * @var Element\ElementInterface[]
     */
    protected array $elements = [];

    /**
     * @internal
     *
     */
    protected array $elementIds = [];

    public function getType(): string
    {
        return 'relations';
    }

    public function setElements(): static
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

    public function getElementIds(): array
    {
        return $this->elementIds;
    }

    public function getData(): mixed
    {
        $this->setElements();

        return $this->elements;
    }

    public function getDataForResource(): mixed
    {
        return $this->elementIds;
    }

    public function getDataEditmode(): array
    {
        $this->setElements();
        $return = [];

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

        return $return;
    }

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

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->setDataFromEditmode($unserializedData);

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
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
    public function getElements(): array
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

    public function isEmpty(): bool
    {
        $this->setElements();

        return count($this->elements) > 0 ? false : true;
    }

    public function resolveDependencies(): array
    {
        $this->setElements();
        $dependencies = [];

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

        return $dependencies;
    }

    public function rewriteIds(array $idMapping): void
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

    public function __sleep(): array
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

    public function load(): void
    {
        $this->setElements();
    }

    /**
     * Methods for Iterator
     */
    public function rewind(): void
    {
        $this->setElements();
        reset($this->elements);
    }

    public function current(): false|Element\ElementInterface
    {
        $this->setElements();

        return current($this->elements);
    }

    public function key(): ?int
    {
        $this->setElements();

        return key($this->elements);
    }

    public function next(): void
    {
        $this->setElements();
        next($this->elements);
    }

    public function valid(): bool
    {
        $this->setElements();

        $el = $this->current();
        if (
            $el instanceof Element\ElementInterface &&
            Element\Service::doHideUnpublished($el) &&
            !Element\Service::isPublished($el)
        ) {
            $this->next();
        }

        return $this->current() !== false;
    }
}
