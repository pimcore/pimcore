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

    /**
     * @var string
     */
    protected string $text;

    /**
     * @var string|null
     */
    protected ?string $internalType;

    /**
     * @var int|null
     */
    protected ?int $internal;

    /**
     * @var string
     */
    protected string $direct;

    /**
     * @var string|null
     */
    protected ?string $linktype;

    /**
     * @var string
     */
    protected string $target;

    /**
     * @var string
     */
    protected string $parameters;

    /**
     * @var string
     */
    protected string $anchor;

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var string
     */
    protected string $accesskey;

    /**
     * @var string
     */
    protected string $rel;

    /**
     * @var string
     */
    protected string $tabindex;

    /**
     * @var string
     */
    protected string $class;

    /**
     * @var string
     */
    protected string $attributes;

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->text = $text;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalType(): ?string
    {
        return $this->internalType;
    }

    /**
     * @param string|null $internalType
     *
     * @return $this
     */
    public function setInternalType(?string $internalType): static
    {
        $this->internalType = $internalType;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return int|null
     */
    public function getInternal(): ?int
    {
        return $this->internal;
    }

    /**
     * @param int|null $internal
     *
     * @return $this
     */
    public function setInternal(?int $internal): static
    {
        $this->internal = $internal;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getDirect(): string
    {
        return $this->direct;
    }

    /**
     * @param string $direct
     *
     * @return $this
     */
    public function setDirect(string $direct): static
    {
        $this->direct = $direct;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLinktype(): ?string
    {
        return $this->linktype;
    }

    /**
     * @param string|null $linktype
     *
     * @return $this
     */
    public function setLinktype(?string $linktype): static
    {
        $this->linktype = $linktype;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setTarget(string $target): static
    {
        $this->target = $target;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     *
     * @return $this
     */
    public function setParameters(string $parameters): static
    {
        $this->parameters = $parameters;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getAnchor(): string
    {
        return $this->anchor;
    }

    /**
     * @param string $anchor
     *
     * @return $this
     */
    public function setAnchor(string $anchor): static
    {
        $this->anchor = $anchor;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getAccesskey(): string
    {
        return $this->accesskey;
    }

    /**
     * @param string $accesskey
     *
     * @return $this
     */
    public function setAccesskey(string $accesskey): static
    {
        $this->accesskey = $accesskey;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getRel(): string
    {
        return $this->rel;
    }

    /**
     * @param string $rel
     *
     * @return $this
     */
    public function setRel(string $rel): static
    {
        $this->rel = $rel;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTabindex(): string
    {
        return $this->tabindex;
    }

    /**
     * @param string $tabindex
     *
     * @return $this
     */
    public function setTabindex(string $tabindex): static
    {
        $this->tabindex = $tabindex;
        $this->markMeDirty();

        return $this;
    }

    public function setAttributes(string $attributes)
    {
        $this->attributes = $attributes;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getAttributes(): string
    {
        return $this->attributes;
    }

    public function setClass(string $class)
    {
        $this->class = $class;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $path
     *
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

    /**
     * @return string
     */
    public function getPath(): string
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getElement() instanceof ElementInterface) {
                $path = $this->getElement()->getFullPath();
            }
        } else {
            $path = $this->getDirect();
        }

        return $path;
    }

    /**
     * Returns the plain text path of the link
     *
     * @return string
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
            $path = $this->getDirect();
        }

        if (strlen($this->getParameters()) > 0) {
            $path .= '?' . str_replace('?', '', $this->getParameters());
        }
        if (strlen($this->getAnchor()) > 0) {
            $path .= '#' . str_replace('#', '', $this->getAnchor());
        }

        return $path;
    }

    /**
     * @return Document|Asset|DataObject|null
     */
    public function getElement(): DataObject|Asset|Document|null
    {
        $element = null;

        if ($this->internalType == 'document') {
            $element = Document::getById($this->internal);
        } elseif ($this->internalType == 'asset') {
            $element = Asset::getById($this->internal);
        } elseif ($this->internalType == 'object') {
            $element = Concrete::getById($this->internal);
        }

        return $element;
    }

    /**
     * @param ElementInterface $object
     *
     * @return $this
     */
    public function setElement(ElementInterface $object): static
    {
        if ($object instanceof ElementInterface) {
            $this->internal = $object->getId();
            $this->internalType = Service::getElementType($object);
        }

        $this->markMeDirty();

        return $this;
    }

    /**
     * @deprecated use getElement() instead - will be removed in Pimcore 11
     *
     * @return Asset|DataObject|Document|null
     */
    public function getObject(): DataObject|Asset|Document|null
    {
        return $this->getElement();
    }

    /**
     *@deprecated use setElement() instead - will be removed in Pimcore 11
     *
     */
    public function setObject(ElementInterface $object): static
    {
        return $this->setElement($object);
    }

    /**
     * @return string
     */
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

        if (empty($this->text)) {
            return '';
        }

        return '<a href="' . $this->getHref() . '" ' . implode(' ', $attribs) . '>' . htmlspecialchars($this->getText()) . '</a>';
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setValues(array $data = []): static
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHtml();
    }
}
