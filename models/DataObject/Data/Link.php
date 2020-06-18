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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    protected $text;

    /**
     * @var string|null
     */
    protected $internalType;

    /**
     * @var int
     */
    protected $internal;

    /**
     * @var string
     */
    protected $direct;

    /**
     * @var string
     */
    protected $linktype;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var string
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $anchor;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $accesskey;

    /**
     * @var string
     */
    protected $rel;

    /**
     * @var string
     */
    protected $tabindex;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $attributes;

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalType()
    {
        return $this->internalType;
    }

    /**
     * @param string $internalType
     *
     * @return $this
     */
    public function setInternalType($internalType)
    {
        $this->internalType = $internalType;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return int
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * @param int $internal
     *
     * @return $this
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getDirect()
    {
        return $this->direct;
    }

    /**
     * @param string $direct
     *
     * @return $this
     */
    public function setDirect($direct)
    {
        $this->direct = $direct;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getLinktype()
    {
        return $this->linktype;
    }

    /**
     * @param string $linktype
     *
     * @return $this
     */
    public function setLinktype($linktype)
    {
        $this->linktype = $linktype;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     *
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @param string $anchor
     *
     * @return $this
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->accesskey;
    }

    /**
     * @param string $accesskey
     *
     * @return $this
     */
    public function setAccesskey($accesskey)
    {
        $this->accesskey = $accesskey;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getRel()
    {
        return $this->rel;
    }

    /**
     * @param string $rel
     *
     * @return $this
     */
    public function setRel($rel)
    {
        $this->rel = $rel;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->tabindex;
    }

    /**
     * @param string $tabindex
     *
     * @return $this
     */
    public function setTabindex($tabindex)
    {
        $this->tabindex = $tabindex;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @param string $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
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
    public function getPath()
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset || $this->getObject() instanceof Concrete) {
                $path = $this->getObject()->getFullPath();
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
    public function getHref()
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $path = $this->getObject()->getFullPath();
            } elseif ($this->getObject() instanceof Concrete) {
                if ($linkGenerator = $this->getObject()->getClass()->getLinkGenerator()) {
                    $path = $linkGenerator->generate($this->getObject(), [
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
    public function getObject()
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
    public function setObject($object)
    {
        if ($object instanceof ElementInterface) {
            $this->internal = $object->getId();
            $this->internalType = Service::getElementType($object);
        }

        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getHtml()
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
    public function isEmpty()
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
    public function setValues($data = [])
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
