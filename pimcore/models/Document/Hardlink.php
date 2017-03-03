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
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Hardlink extends Document
{
    use Element\ChildsCompatibilityTrait;

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
     * @return Document\PageSnippet
     */
    public function getSourceDocument()
    {
        if ($this->getSourceId()) {
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

            $dependencies[$key] = [
                "id" => $this->getSourceDocument()->getId(),
                "type" => "document"
            ];
        }

        return $dependencies;
    }

    /**
     * Resolves dependencies and create tags for caching out of them
     *
     * @param array $tags
     * @return array
     */
    public function getCacheTags($tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        $tags = parent::getCacheTags($tags);

        if ($this->getSourceDocument()) {
            if ($this->getSourceDocument()->getId() != $this->getId() and !array_key_exists($this->getSourceDocument()->getCacheTag(), $tags)) {
                $tags = $this->getSourceDocument()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param $childsFromSource
     * @return Hardlink
     */
    public function setChildrenFromSource($childsFromSource)
    {
        $this->childsFromSource = (bool) $childsFromSource;

        return $this;
    }

    /**
     * @deprecated
     * @param $childsFromSource
     * @return Hardlink
     */
    public function setChildsFromSource($childsFromSource)
    {
        return $this->setChildrenFromSource($childsFromSource);
    }

    /**
     * @return boolean
     */
    public function getChildrenFromSource()
    {
        return $this->childsFromSource;
    }

    /**
     * @deprecated
     * @return bool
     */
    public function getChildsFromSource()
    {
        return $this->getChildrenFromSource();
    }

    /**
     * @param $sourceId
     * @return $this
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param $propertiesFromSource
     * @return $this
     */
    public function setPropertiesFromSource($propertiesFromSource)
    {
        $this->propertiesFromSource = (bool) $propertiesFromSource;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getPropertiesFromSource()
    {
        return $this->propertiesFromSource;
    }

    /**
     * @return array|null|Model\Property[]
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            $properties = parent::getProperties();

            if ($this->getPropertiesFromSource() && $this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getProperties();
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            } elseif ($this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getDao()->getProperties(false, true);
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

    /**
     * @param bool $unpublished
     * @return array|null
     */
    public function getChildren($unpublished = false)
    {
        if ($this->childs === null) {
            $childs = parent::getChildren();

            $sourceChildren = [];
            if ($this->getChildrenFromSource() && $this->getSourceDocument() && !\Pimcore::inAdmin()) {
                $sourceChildren = $this->getSourceDocument()->getChildren();
                foreach ($sourceChildren as &$c) {
                    $c = Document\Hardlink\Service::wrap($c);
                    $c->setHardLinkSource($this);
                    $c->setPath(preg_replace("@^" . preg_quote($this->getSourceDocument()->getRealFullPath()) . "@", $this->getRealFullPath(), $c->getRealPath()));
                }
            }

            $childs = array_merge($sourceChildren, $childs);
            $this->setChildren($childs);
        }

        return $this->childs;
    }

    /**
     * hast to overwrite the resource implementation because there can be inherited childs
     * @return bool
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }


    /**
     * @see Document::delete
     */
    public function delete()
    {

        // hardlinks cannot have direct children in "real" world, so we have to empty them before we delete it
        $this->childs = [];

        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition("target = ?", $this->getId());
        $redirects->load();

        foreach ($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        parent::delete();

        // we re-enable the children functionality by setting them to NULL, if requested they'll be loaded again
        // -> see $this->getChilds() , doesn't make sense when deleting an item but who knows, ... ;-)
        $this->childs = null;
    }

    /**
     *
     */
    protected function update()
    {
        $oldPath = $this->getDao()->getCurrentFullPath();

        parent::update();

        $config = \Pimcore\Config::getSystemConfig();
        if ($oldPath && $config->documents->createredirectwhenmoved && $oldPath != $this->getRealFullPath()) {
            // create redirect for old path
            $redirect = new Redirect();
            $redirect->setTarget($this->getId());
            $redirect->setSource("@" . $oldPath . "/?@");
            $redirect->setStatusCode(301);
            $redirect->setExpiry(time() + 86400 * 60); // this entry is removed automatically after 60 days
            $redirect->save();
        }
    }
}
