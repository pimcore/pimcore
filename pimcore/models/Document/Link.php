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

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;

class Link extends Model\Document
{

    /**
     * Contains the ID of the internal ID
     *
     * @var integer
     */
    public $internal;

    /**
     * Contains the type of the internal ID
     *
     * @var string
     */
    public $internalType;

    /**
     * Contains object of linked Document|Asset
     *
     * @var Document | Asset
     */
    public $object;

    /**
     * Contains the direct link as plain text
     *
     * @var string
     */
    public $direct = "";

    /**
     * Type of the link (internal/direct)
     *
     * @var string
     */
    public $linktype = "internal";


    /**
     * static type of this object
     *
     * @var string
     */
    public $type = "link";

    /**
     * path of the link
     *
     * @var string
     */
    public $href = "";


    /**
     * @see Document::resolveDependencies
     * @return array
     */
    public function resolveDependencies()
    {

        $dependencies = parent::resolveDependencies();

        if ($this->getLinktype() == "internal") {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $key = $this->getInternalType() . "_" . $this->getObject()->getId();

                $dependencies[$key] = array(
                    "id" => $this->getObject()->getId(),
                    "type" => $this->getInternalType()
                );
            }
        }

        return $dependencies;
    }

    /**
     * Resolves dependencies and create tags for caching out of them
     *
     * @return array
     */
    public function getCacheTags($tags = array())
    {
        $tags = is_array($tags) ? $tags : array();
        
        $tags = parent::getCacheTags($tags);

        if ($this->getLinktype() == "internal") {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                if ($this->getObject()->getId() != $this->getId() and !array_key_exists($this->getObject()->getCacheTag(), $tags)) {
                    $tags = $this->getObject()->getCacheTags($tags);
                }
            }
        }
        return $tags;
    }


    /**
     * Returns the plain text path of the link
     *
     * @return string
     */
    public function getHref()
    {
        $path = "";
        if ($this->getLinktype() == "internal") {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $path = $this->getObject()->getFullPath();
            }
        }
        else {
            $path = $this->getDirect();
        }

        $this->href = $path;
        return $path;
    }


    /**
     * Returns the path of the link including the anchor and parameters
     *
     * @return string
     */
    public function getLink () {
        $path = $this->getHref();

        if (strlen($this->getParameters()) > 0) {
            $path .= "?" . str_replace("?", "", $this->getParameters());
        }
        if (strlen($this->getAnchor()) > 0) {
            $path .= "#" . str_replace("#", "", $this->getAnchor());
        }

        return $path;
    }


    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getTarget()
    {
        return $this->getProperty("navigation_target");
    }

    /**
     * setProperty method should be used instead
     *
     * @deprecated
     * @param string $target
     * @return string
     */
    public function setTarget($target)
    {
        $this->setProperty("navigation_target", "text", $target, false);
        return $this;
    }

    /**
     * Returns the id of the internal document|asset which is linked
     *
     * @return integer
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * Returns the direct link (eg. http://www.pimcore.org/test)
     *
     * @return string
     */
    public function getDirect()
    {
        return $this->direct;
    }

    /**
     * Returns the type of the link (internal/direct)
     *
     * @return string
     */
    public function getLinktype()
    {
        return $this->linktype;
    }

    /**
     * @param integer $internal
     * @return void
     */
    public function setInternal($internal)
    {
        if (!empty($internal)) {
            $this->internal = (int) $internal;
            $this->setObjectFromId();
        }
        else {
            $this->internal = null;
        }
        return $this;
    }

    /**
     * @param string $direct
     * @return void
     */
    public function setDirect($direct)
    {
        $this->direct = $direct;
        return $this;
    }

    /**
     * @param string $linktype
     * @return void
     */
    public function setLinktype($linktype)
    {
        $this->linktype = $linktype;
        return $this;
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getName()
    {
        return $this->getProperty("navigation_name");
    }

    /**
     * setProperty method should be used instead
     *
     * @deprecated
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->setProperty("navigation_name", "text", $name, false);
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
     * @param string $type
     * @return void
     */
    public function setInternalType($type)
    {
        $this->internalType = $type;
        return $this;
    }

    /**
     * @return Document|Asset
     */
    public function getObject()
    {
        if ($this->object instanceof Document || $this->object instanceof Asset) {
            return $this->object;
        }
        else {
            if ($this->setObjectFromId()) {
                return $this->object;
            }
        }
        return false;
    }

    /**
     * @return void
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return void
     */
    public function setObjectFromId()
    {
        if ($this->internalType == "document") {
            $this->object = Document::getById($this->internal);
        }
        else if ($this->internalType == "asset") {
            $this->object = Asset::getById($this->internal);
        }
        return $this->object;
    }


    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getParameters()
    {
        return $this->getProperty("navigation_parameters");
    }

    /**
     * @param $parameters
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->setProperty("navigation_parameters", "text", $parameters, false);
        return $this;
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getAnchor()
    {
        return $this->getProperty("navigation_anchor");
    }

    /**
     * @param $anchor
     * @return $this
     */
    public function setAnchor($anchor)
    {
        $this->setProperty("navigation_anchor", "text", $anchor, false);
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
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getAccesskey()
    {
        return $this->getProperty("accesskey");
    }

    /**
     * @param $accesskey
     * @return $this
     */
    public function setAccesskey($accesskey)
    {
        $this->setProperty("accesskey", "text", $accesskey, false);
        return $this;
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getRel()
    {
        return $this->getProperty("navigation_relation");
    }

    /**
     * @param $rel
     * @return $this
     */
    public function setRel($rel)
    {
        $this->setProperty("navigation_relation", "text", $rel, false);
        return $this;
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getTabindex()
    {
        return $this->getProperty("tabindex");
    }

    /**
     * @param $tabindex
     * @return $this
     */
    public function setTabindex($tabindex)
    {
        $this->setProperty("navigation_tabindex", "text", $tabindex, false);
        return $this;
    }

    /**
     * returns the ready-use html for this link
     *
     * @return string
     */
    public function getHtml()
    {

        $attributes = array("rel", "tabindex", "accesskey", "title", "name", "target");
        $attribs = array();
        foreach ($attributes as $a) {
            $attribs[] = $a . '="' . $this->$a . '"';
        }

        return '<a href="' . $this->getLink() . '" ' . implode(" ", $attribs) . '>' . htmlspecialchars($this->getProperty("navigation_name")) . '</a>';
    }
}
