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

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Relation extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * ID of the source object
     *
     * @internal
     *
     */
    protected ?int $id = null;

    /**
     * Type of the source object (document, asset, object)
     *
     * @internal
     *
     */
    protected ?string $type = null;

    /**
     * Subtype of the source object (eg. page, link, video, news, ...)
     *
     * @internal
     *
     */
    protected ?string $subtype = null;

    /**
     * Contains the source object
     *
     * @internal
     *
     */
    protected mixed $element = null;

    public function getType(): string
    {
        //TODO: getType != $type ... that might be dangerous
        return 'relation';
    }

    public function getData(): mixed
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subtype' => $this->subtype,
        ];
    }

    public function getDataEditmode(): ?array
    {
        $this->setElement();

        if ($this->element instanceof Element\ElementInterface) {
            return [
                'id' => $this->id,
                'path' => $this->element->getRealFullPath(),
                'elementType' => $this->type,
                'subtype' => $this->subtype,
            ];
        }

        return null;
    }

    public function frontend()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return '';
        }

        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return '';
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->id = $unserializedData['id'] ?? null;
        $this->type = $unserializedData['type'] ?? null;
        $this->subtype = $unserializedData['subtype'] ?? null;

        $this->setElement();

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->subtype = $data['subtype'] ?? null;

        $this->setElement();

        return $this;
    }

    /**
     * Sets the element by the data stored for the object
     *
     * @return $this
     */
    private function setElement(): static
    {
        if (!$this->element && $this->type && $this->id) {
            $this->element = Element\Service::getElementById($this->type, $this->id);
        }

        return $this;
    }

    /**
     * Returns one of them: Document, Object, Asset
     *
     * @return Element\ElementInterface|false|null
     */
    public function getElement(): bool|Element\ElementInterface|null
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return false;
        }

        return $this->element;
    }

    /**
     * Returns the path of the linked element
     *
     * @return string|false|null
     */
    public function getFullPath(): bool|string|null
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return false;
        }
        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return null;
    }

    public function isEmpty(): bool
    {
        $this->setElement();

        if ($this->getElement() instanceof Element\ElementInterface) {
            return false;
        }

        return true;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [];
        $this->setElement();

        if ($this->element instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($this->element);
            $key = $elementType . '_' . $this->element->getId();
            $dependencies[$key] = [
                'id' => $this->element->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    public function checkValidity(): bool
    {
        $sane = true;
        if ($this->id) {
            $el = Element\Service::getElementById($this->type, $this->id);
            if (!$el instanceof Element\ElementInterface) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent '.$this->type.' with id ['.$this->id.']');
                $this->id = null;
                $this->type = null;
                $this->subtype = null;
                $this->element = null;
            }
        }

        return $sane;
    }

    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['element'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function load(): void
    {
        if (!$this->element) {
            $this->setElement();
        }
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setSubtype(string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function rewriteIds(array $idMapping): void
    {
        if (array_key_exists($this->type, $idMapping) && array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->id = $idMapping[$this->type][$this->getId()];
        }
    }
}
