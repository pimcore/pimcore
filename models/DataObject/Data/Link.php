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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class Link implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    use ObjectVarTrait;

    protected string $text = '';

    protected ?string $internalType = null;

    protected ?int $internal = null;

    protected ?string $direct = null;

    protected ?string $linktype = null;

    protected ?string $target = null;

    protected string $parameters = '';

    protected string $anchor = '';

    protected string $title = '';

    protected string $accesskey = '';

    protected string $rel = '';

    protected string $tabindex = '';

    protected string $class = '';

    protected string $attributes = '';

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->text = $text;
        $this->markMeDirty();

        return $this;
    }

    public function getInternalType(): ?string
    {
        return $this->internalType;
    }

    /**
     * @return $this
     */
    public function setInternalType(?string $internalType): static
    {
        $this->internalType = $internalType;
        $this->markMeDirty();

        return $this;
    }

    public function getInternal(): ?int
    {
        return $this->internal;
    }

    /**
     * @return $this
     */
    public function setInternal(?int $internal): static
    {
        $this->internal = $internal;
        $this->markMeDirty();

        return $this;
    }

    public function getDirect(): ?string
    {
        return $this->direct;
    }

    /**
     * @return $this
     */
    public function setDirect(?string $direct): static
    {
        $this->direct = $direct;
        $this->markMeDirty();

        return $this;
    }

    public function getLinktype(): ?string
    {
        return $this->linktype;
    }

    /**
     * @return $this
     */
    public function setLinktype(?string $linktype): static
    {
        $this->linktype = $linktype;
        $this->markMeDirty();

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @return $this
     */
    public function setTarget(?string $target): static
    {
        $this->target = $target;
        $this->markMeDirty();

        return $this;
    }

    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * @return $this
     */
    public function setParameters(string $parameters): static
    {
        $this->parameters = $parameters;
        $this->markMeDirty();

        return $this;
    }

    public function getAnchor(): string
    {
        return $this->anchor;
    }

    /**
     * @return $this
     */
    public function setAnchor(string $anchor): static
    {
        $this->anchor = $anchor;
        $this->markMeDirty();

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->markMeDirty();

        return $this;
    }

    public function getAccesskey(): string
    {
        return $this->accesskey;
    }

    /**
     * @return $this
     */
    public function setAccesskey(string $accesskey): static
    {
        $this->accesskey = $accesskey;
        $this->markMeDirty();

        return $this;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    /**
     * @return $this
     */
    public function setRel(string $rel): static
    {
        $this->rel = $rel;
        $this->markMeDirty();

        return $this;
    }

    public function getTabindex(): string
    {
        return $this->tabindex;
    }

    /**
     * @return $this
     */
    public function setTabindex(string $tabindex): static
    {
        $this->tabindex = $tabindex;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return $this
     */
    public function setAttributes(string $attributes): static
    {
        $this->attributes = $attributes;
        $this->markMeDirty();

        return $this;
    }

    public function getAttributes(): string
    {
        return $this->attributes;
    }

    /**
     * @return $this
     */
    public function setClass(string $class): static
    {
        $this->class = $class;
        $this->markMeDirty();

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return $this
     */
    public function setPath(string $path): static
    {
        if (!empty($path)) {
            $matchedElement = null;
            if ($this->getLinktype() == 'internal' && $this->getInternalType()) {
                $matchedElement = Service::getElementByPath($this->getInternalType(), $path);
                if ($matchedElement) {
                    $this->linktype = 'internal';
                    $this->internalType = $this->getInternalType();
                    $this->internal = $matchedElement->getId();
                }
            }

            if (!$matchedElement) {
                if ($document = Document::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'document';
                    $this->internal = $document->getId();
                } elseif ($asset = Asset::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'asset';
                    $this->internal = $asset->getId();
                } elseif ($object = Concrete::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'object';
                    $this->internal = $object->getId();
                } else {
                    $this->linktype = 'direct';
                    $this->internalType = null;
                    $this->direct = $path;
                }
            }
        }
        $this->markMeDirty();

        return $this;
    }

    public function getPath(): string
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getElement() instanceof ElementInterface) {
                $path = $this->getElement()->getFullPath();
            }
        } else {
            $path = $this->getDirect() ?? '';
        }

        return $path;
    }

    /**
     * Returns the plain text path of the link
     *
     */
    public function getHref(): string
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getElement() instanceof Document || $this->getElement() instanceof Asset) {
                $path = $this->getElement()->getFullPath();
            } elseif ($this->getElement() instanceof Concrete) {
                if ($linkGenerator = $this->getElement()->getClass()->getLinkGenerator()) {
                    $path = $linkGenerator->generate($this->getElement(), [
                        'context' => $this,
                    ]);
                }
            }
        } else {
            $path = $this->getDirect() ?? '';
        }

        if (strlen($this->getParameters()) > 0) {
            $path .= '?' . str_replace('?', '', $this->getParameters());
        }
        if (strlen($this->getAnchor()) > 0) {
            $path .= '#' . str_replace('#', '', $this->getAnchor());
        }

        return $path;
    }

    public function getElement(): DataObject|Asset|Document|null
    {
        $element = null;

        if ($this->internal !== null) {
            if ($this->internalType === 'document') {
                $element = Document::getById($this->internal);
            } elseif ($this->internalType === 'asset') {
                $element = Asset::getById($this->internal);
            } elseif ($this->internalType === 'object') {
                $element = Concrete::getById($this->internal);
            }
        }

        return $element;
    }

    /**
     * @return $this
     */
    public function setElement(ElementInterface $object): static
    {
        $this->internal = $object->getId();
        $this->internalType = Service::getElementType($object);
        $this->markMeDirty();

        return $this;
    }

    public function getHtml(): string
    {
        $attributes = ['rel', 'tabindex', 'accesskey', 'title', 'target', 'class'];
        $attribs = [];
        foreach ($attributes as $a) {
            if ($this->$a) {
                $attribs[] = $a . '="' . $this->$a . '"';
            }
        }

        if ($this->getAttributes()) {
            $attribs[] = $this->getAttributes();
        }

        $text = $this->getText();

        if (empty($text)) {
            return '';
        }

        return '<a href="' . $this->getHref() . '" ' . implode(' ', $attribs) . '>' . htmlspecialchars($text) . '</a>';
    }

    public function isEmpty(): bool
    {
        $vars = $this->getObjectVars();
        foreach ($vars as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return $this
     */
    public function setValues(array $data = []): static
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        $this->markMeDirty();

        return $this;
    }

    public function __toString(): string
    {
        return $this->getHtml();
    }

    /**
     * @internal
     *
     * https://github.com/pimcore/pimcore/pull/15926
     * used for non-nullable properties stored with null
     *
     * @TODO: Remove in Pimcore 12
     *
     */
    public function __unserialize(array $data): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            $this->$property = $data["\0*\0".$property] ?? $value;
        }
    }
}
