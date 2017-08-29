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
use Pimcore\Model\Document;
use Pimcore\Model\DataObject\Concrete;

class Link
{
    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $internalType;

    /**
     * @var string
     */
    public $internal;

    /**
     * @var string
     */
    public $direct;

    /**
     * @var string
     */
    public $linktype;

    /**
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $parameters;

    /**
     * @var string
     */
    public $anchor;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $accesskey;

    /**
     * @var string
     */
    public $rel;

    /**
     * @var string
     */
    public $tabindex;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $attributes;

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

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
     * @param $internalType
     *
     * @return $this
     */
    public function setInternalType($internalType)
    {
        $this->internalType = $internalType;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * @param $internal
     *
     * @return $this
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;
        if (!empty($internal)) {
            $this->setObjectFromId();
        }

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
     * @param $direct
     *
     * @return $this
     */
    public function setDirect($direct)
    {
        $this->direct = $direct;

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
     * @param $linktype
     *
     * @return $this
     */
    public function setLinktype($linktype)
    {
        $this->linktype = $linktype;

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
     * @param $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

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
     * @param $parameters
     *
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

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
     * @param $anchor
     *
     * @return $this
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;

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
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

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
     * @param $accesskey
     *
     * @return $this
     */
    public function setAccesskey($accesskey)
    {
        $this->accesskey = $accesskey;

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
     * @param $rel
     *
     * @return $this
     */
    public function setRel($rel)
    {
        $this->rel = $rel;

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
     * @param $tabindex
     *
     * @return $this
     */
    public function setTabindex($tabindex)
    {
        $this->tabindex = $tabindex;

        return $this;
    }

    /**
     * @param string $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
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
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        if (!empty($path)) {
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
                $this->direct = $path;
            }
        }

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
                        'context' => $this
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

        $this->href = $path;

        return $path;
    }

    /**
     * @return Document|Asset|bool
     *
     * @todo: $this->object not found in class
     */
    public function getObject()
    {
        if ($this->object instanceof Document || $this->object instanceof Asset || $this->object instanceof Concrete) {
            return $this->object;
        } else {
            if ($this->setObjectFromId()) {
                return $this->object;
            }
        }

        return false;
    }

    /**
     * @param $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return Asset|Document
     *
     * @todo: $this->object not found in class
     */
    public function setObjectFromId()
    {
        if ($this->internalType == 'document') {
            $this->object = Document::getById($this->internal);
        } elseif ($this->internalType == 'asset') {
            $this->object = Asset::getById($this->internal);
        } elseif ($this->internalType == 'object') {
            $this->object = Concrete::getById($this->internal);
        }

        return $this->object;
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
