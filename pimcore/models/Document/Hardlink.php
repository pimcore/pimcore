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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Hardlink extends Document
{

    /**
     * static type of this object
     *
     * @var string
     */
    public $type = "hardlink";

    /**
     * @var int
     */
    public $sourceId;

    /**
     * @var bool
     */
    public $propertiesFromSource;

    /**
     * @var bool
     */
    public $childsFromSource;


    /**
     * @return Document_PageSnippet
     */
    public function getSourceDocument () {
        if($this->getSourceId()) {
            return Document::getById($this->getSourceId());
        }

        return null;
    }

    /**
     * @see Document::resolveDependencies
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = parent::resolveDependencies();

        if ($this->getSourceDocument() instanceof Document) {
            $key = "document_" . $this->getSourceDocument()->getId();

            $dependencies[$key] = array(
                "id" => $this->getSourceDocument()->getId(),
                "type" => "document"
            );
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

        if ($this->getSourceDocument()) {
            if ($this->getSourceDocument()->getId() != $this->getId() and !array_key_exists($this->getSourceDocument()->getCacheTag(), $tags)) {
                $tags = $this->getSourceDocument()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param boolean $childsFromSource
     */
    public function setChildsFromSource($childsFromSource)
    {
        $this->childsFromSource = (bool) $childsFromSource;
    }

    /**
     * @return boolean
     */
    public function getChildsFromSource()
    {
        return $this->childsFromSource;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param boolean $propertiesFromSource
     */
    public function setPropertiesFromSource($propertiesFromSource)
    {
        $this->propertiesFromSource = (bool) $propertiesFromSource;
    }

    /**
     * @return boolean
     */
    public function getPropertiesFromSource()
    {
        return $this->propertiesFromSource;
    }


    public function getProperties() {

        if ($this->properties === null) {
            $properties = parent::getProperties();

            if($this->getPropertiesFromSource() && $this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getProperties();
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            } else if ($this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getResource()->getProperties(false,true);
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            }

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    public function getChilds() {

        if ($this->childs === null) {
            $childs = parent::getChilds();

            $sourceChilds = array();
            if($this->getChildsFromSource() && $this->getSourceDocument() && !Pimcore::inAdmin()) {
                $sourceChilds = $this->getSourceDocument()->getChilds();
                foreach($sourceChilds as &$c) {
                    $c = Document_Hardlink_Service::wrap($c);
                    $c->setHardLinkSource($this);
                    $c->setPath(preg_replace("@^" . preg_quote($this->getSourceDocument()->getFullpath()) . "@", $this->getFullpath(), $c->getPath()));
                }
            }

            $childs = array_merge($sourceChilds, $childs);
            $this->setChilds($childs);
        }

        return $this->childs;
    }

    /**
     * hast to overwrite the resource implementation because there can be inherited childs
     * @return bool
     */
    public function hasChilds() {
        return count($this->getChilds()) > 0;
    }



}
