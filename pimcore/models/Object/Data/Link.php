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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Data_Link {

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
    
    
    public function getText() {
        return $this->text;
    }
    
    public function setText ($text) {
        $this->text = $text;
    }
    
    public function getInternalType() {
        return $this->internalType;
    }
    
    public function setInternalType ($internalType) {
        $this->internalType = $internalType;
    }
    
    public function getInternal() {
        return $this->internal;
    }
    
    public function setInternal ($internal) {
        $this->internal = $internal;
        if (!empty($internal)) {
            $this->setObjectFromId();
        }
    }
    
    public function getDirect() {
        return $this->direct;
    }
    
    public function setDirect ($direct) {
        $this->direct = $direct;
    }
    
    public function getLinktype() {
        return $this->linktype;
    }
    
    public function setLinktype ($linktype) {
        $this->linktype = $linktype;
    }
    
    public function getTarget() {
        return $this->target;
    }
    
    public function setTarget ($target) {
        $this->target = $target;
    }
    
    public function getParameters() {
        return $this->parameters;
    }
    
    public function setParameters ($parameters) {
        $this->parameters = $parameters;
    }
    
    public function getAnchor() {
        return $this->anchor;
    }
    
    public function setAnchor ($anchor) {
        $this->anchor = $anchor;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle ($title) {
        $this->title = $title;
    }
    
    public function getAccesskey() {
        return $this->accesskey;
    }
    
    public function setAccesskey ($accesskey) {
        $this->accesskey = $accesskey;
    }
    
    public function getRel() {
        return $this->rel;
    }
    
    public function setRel ($rel) {
        $this->rel = $rel;
    }
    
    public function getTabindex() {
        return $this->tabindex;
    }
    
    public function setTabindex ($tabindex) {
        $this->tabindex = $tabindex;
    }
    
    
    public function setPath ($path) {
        if (!empty($path)) {
            if ($document = Document::getByPath($path)) {
                $this->linktype = "internal";
                $this->internalType = "document";
                $this->internal = $document->getId();
            }
            else if ($asset = Asset::getByPath($path)) {
                $this->linktype = "internal";
                $this->internalType = "asset";
                $this->internal = $asset->getId();
            }
            else {
                $this->linktype = "direct";
                $this->direct = $path;
            }
        }
    }
    
    public function getPath () {
        $path = "";
        if ($this->getLinktype() == "internal") {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $path = $this->getObject()->getFullPath();
            }
        }
        else {
            $path = $this->getDirect();
        }
        
        return $path;
    }
    
    /**
     * Returns the plain text path of the link
     *
     * @return string
     */
    public function getHref() {
        $path = "";
        if ($this->getLinktype() == "internal") {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $path = $this->getObject()->getFullPath();
            }
        }
        else {
            $path = $this->getDirect();
        }

        if (strlen($this->getParameters()) > 0) {
            $path .= "?" . str_replace("?", "", $this->getParameters());
        }
        if (strlen($this->getAnchor()) > 0) {
            $path .= "#" . str_replace("#", "", $this->getAnchor());
        }

        $this->href = $path;
        return $path;
    }
    
    /**
     * @return Document|Asset
     */
    public function getObject() {
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
    public function setObject($object) {
        $this->object = $object;
    }

    /**
     * @return void
     */
    public function setObjectFromId() {
        if ($this->internalType == "document") {
            $this->object = Document::getById($this->internal);
        }
        else if ($this->internalType == "asset") {
            $this->object = Asset::getById($this->internal);
        }
        return $this->object;
    }
    
    public function getHtml() {

        $attributes = array("rel", "tabindex", "accesskey", "title","target");
        $attribs = array();
        foreach ($attributes as $a) {
            $attribs[] = $a . '="' . $this->$a . '"';
        }
        
        if(empty($this->text)) {
            return "";
        }
        
        return '<a href="' . $this->getHref() . '" ' . implode(" ", $attribs) . '>' . htmlspecialchars($this->getText()) . '</a>';
    }
    
    
    public function isEmpty() {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if(!empty($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function setValues($data = array()) {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $method = "set" . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
    }
    
    public function __toString() {
        return $this->getHtml();
    }
}