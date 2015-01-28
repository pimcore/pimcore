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
 *
 */

namespace Pimcore\Model;

use Pimcore\Tool;
use Pimcore\Tool\Frontend as FrontendTool;

class Document extends Element\AbstractElement {

    /**
     * possible types of a document
     * @var array
     */
    public static $types = array("folder", "page", "snippet", "link", "hardlink", "email");  //ck added "email"

    /**
     * @param $type
     */
    public static function addDocumentType($type) {
        if(!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }

    /**
     * @var bool
     */
    private static $hidePublished = false;

    /**
     * @param $hidePublished
     */
    public static function setHideUnpublished($hidePublished) {
        self::$hidePublished = $hidePublished;
    }

    /**
     * @return bool
     */
    public static function doHideUnpublished() {
        return self::$hidePublished;
    }

    /**
     * ID of the document
     *
     * @var integer
     */
    public $id;

    /**
     * ID of the parent document, on root document this is null
     *
     * @var integer
     */
    public $parentId;

    /**
     * @var Document
     */
    public $parent;

    /**
     * Type of the document as string (enum)
     * Possible values: page,snippet,link,folder
     *
     * @var string
     */
    public $type;

    /**
     * Filename/Key of the document
     *
     * @var string
     */
    public $key;

    /**
     * Path to the document, not conaining the key (the full path of the parent document)
     *
     * @var string
     */
    public $path;

    /**
     * Sorter index in the tree, can also be used for generating a navigation and so on
     *
     * @var integer
     */
    public $index;

    /**
     * published or not
     *
     * @var bool
     */
    public $published = true;

    /**
     * timestamp of creationdate
     *
     * @var integer
     */
    public $creationDate;

    /**
     * timestamp of modificationdate
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var integer
     */
    public $userOwner;

    /**
     * User-ID of the user last modified the document
     *
     * @var integer
     */
    public $userModification;

    /**
     * Permissions for the user which requested this document in editmode*
     */
    public $userPermissions;

    /**
     * Dependencies for this document
     *
     * @var Dependency
     */
    public $dependencies;

    /**
     * List of Property, concerning the folder
     *
     * @var array
     */
    public $properties = null;

    /**
     * Contains a list of child-documents
     *
     * @var array
     */
    public $childs;

    /**
     * Indicator of document has childs or not.
     *
     * @var boolean
     */
    public $hasChilds;

	/**
	 * Contains a list of sibling documents
	 *
	 * @var array
	 */
	public $siblings;

	/**
	 * Indicator if document has siblings or not
	 *
	 * @var boolean
	 */
	public $hasSiblings;

    /**
     * @var string
     */
    public $locked = null;

    /**
     * get possible types
     * @return string
     */
    public static function getTypes() {
        return self::$types;
    }

    /**
     * Static helper to get a Document by it's path, only type Document is returned, not Document\Page, ... (see getConcreteByPath() )
     *
     * @param string $path
     * @return Document
     */
    public static function getByPath($path) {

        $path = Element\Service::correctPath($path);

        try {
            $document = new Document();
            // validate path
            if (Tool::isValidPath($path)) {
                $document->getResource()->getByPath($path);
            }

            return self::getById($document->getId());
        }
        catch (\Exception $e) {
            \Logger::debug($e->getMessage());
        }

        return null;
    }

    /**
     * Static helper to get a Document by it's id, only type Document is returned, not Document\Page, ... (see getConcreteById() )
     *
     * @param integer $id
     * @return Document
     */
    public static function getById($id) {

        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = "document_" . $id;

        try {
            $document = \Zend_Registry::get($cacheKey);
            if(!$document){
                throw new \Exception("Document in registry is null");
            }
        }
        catch (\Exception $e) {
            try {
                if (!$document = Cache::load($cacheKey)) {
                    $document = new Document();
                    $document->getResource()->getById($id);

                    $mappingClass = "\\Pimcore\\Model\\Document\\" . ucfirst($document->getType());

                    // this is the fallback for custom document types using prefixes
                    // so we need to check if the class exists first
                    if(!\Pimcore\Tool::classExists($mappingClass)) {
                        $oldStyleClass = "Document_" . ucfirst($document->getType());
                        if(\Pimcore\Tool::classExists($oldStyleClass)) {
                            $mappingClass = $oldStyleClass;
                        }
                    }
                    $typeClass = Tool::getModelClassMapping($mappingClass);

                    if (Tool::classExists($typeClass)) {
                        $document = new $typeClass();
                        \Zend_Registry::set($cacheKey, $document);
                        $document->getResource()->getById($id);

                        Cache::save($document, $cacheKey);
                    }
                }
                else {
                    \Zend_Registry::set($cacheKey, $document);
                }
            }
            catch (\Exception $e) {
                \Logger::warning($e->getMessage());
                return null;
            }
        }

        if(!$document) {
            return null;
        }

        return $document;
    }

    /**
     * Static helper to get a concrete implementation of a document by it's id, this method returns the concrete object to retrieve only the basic object use getById()
     *
     * @param Document|integer $id
     * @return Document\Page|Document\Snippet|Document\Folder|Document\Link
     */
    public static function getConcreteById($id) {
        return self::getById($id);
    }

    /**
     * Static helper to get a concrete implementation of a document by it's path, this method returns the concrete object to retrieve only the basic object use getById()
     *
     * @param string|Document $path
     * @return Document\Page|Document\Snippet|Document\Folder|Document\Link
     */
    public static function getConcreteByPath($path) {
        return self::getByPath($path);
    }

    /**
     * Static helper to quickly create a new document
     *
     * @param integer $parentId
     * @param array $data
     * @return Document
     */
    public static function create($parentId, $data = array(), $save = true) {

        $document = new static();
        $document->setParentId($parentId);

        foreach ($data as $key => $value) {
            $document->setValue($key, $value);
        }

        if($save) {
            $document->save();
        }

        return $document;
    }


    /**
     * @param array $config
     * @return mixed
     * @throws \Exception
     */
    public static function getList($config = array()) {

        if (is_array($config)) {

            $listClass = "\\Pimcore\\Model\\Document\\Listing";
            $listClass = Tool::getModelClassMapping($listClass);

            if (Tool::classExists($listClass)) {
                $list = new $listClass();

                $list->setValues($config);
                $list->load();

                return $list;
            }
        }

        throw new \Exception("Unable to initiate list class - class not found or invalid configuration");
    }

    /**
     * @param array $config
     * @return total count
     */
    public static function getTotalCount($config = array()) {

        if (is_array($config)) {
            $listClass = "\\Pimcore\\Model\\Document\\Listing";
            $listClass = Tool::getModelClassMapping($listClass);
            $list = new $listClass();

            $list->setValues($config);
            $count = $list->getTotalCount();

            return $count;
        }
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function save() {

        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("document.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("document.preAdd", $this);
        }

        // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
        // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
        // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
        $maxRetries = 5;
        for($retries=0; $retries<$maxRetries; $retries++) {

            $this->beginTransaction();

            try {
                // check for a valid key, home has no key, so omit the check
                if (!Tool::isValidKey($this->getKey()) && $this->getId() != 1) {
                    throw new \Exception("invalid key for document with id [ " . $this->getId() . " ] key is: [" . $this->getKey() . "]");
                }

                $this->correctPath();
                // set date
                $this->setModificationDate(time());

                if(!$this->getCreationDate()) {
                    $this->setCreationDate(time());
                }

                if (!$isUpdate) {
                    $this->getResource()->create();
                }

                // get the old path from the database before the update is done
                $oldPath = null;
                if ($isUpdate) {
                    $oldPath = $this->getResource()->getCurrentFullPath();
                }

                $this->update();

                // if the old path is different from the new path, update all children
                $updatedChildren = array();
                if($oldPath && $oldPath != $this->getFullPath()) {
                    $this->getResource()->updateWorkspaces();
                    $updatedChildren = $this->getResource()->updateChildsPaths($oldPath);
                }

                $this->commit();

                break; // transaction was successfully completed, so we cancel the loop here -> no restart required
            } catch (\Exception $e) {
                try {
                    $this->rollBack();
                } catch (\Exception $er) {
                    // PDO adapter throws exceptions if rollback fails
                    \Logger::error($er);
                }

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if($retries < ($maxRetries-1)) {
                    $run = $retries+1;
                    $waitTime = 100000; // microseconds
                    \Logger::warn("Unable to finish transaction (" . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . " microseconds ... (" . ($run+1) . " of " . $maxRetries . ")");

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    throw $e;
                }
            }
        }

        $additionalTags = array();
        if(isset($updatedChildren) && is_array($updatedChildren)) {
            foreach ($updatedChildren as $documentId) {
                $tag = "document_" . $documentId;
                $additionalTags[] = $tag;

                // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                \Zend_Registry::set($tag, null);
            }
        }
        $this->clearDependentCache($additionalTags);

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("document.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("document.postAdd", $this);
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function correctPath() {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            $parent = Document::getById($this->getParentId());
            if($parent) {
                // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
                // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
                $this->setPath(str_replace("//", "/", $parent->getCurrentFullPath() . "/"));
            } else {
                // parent document doesn't exist anymore, so delete this document
                //$this->delete();

                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath("/");
            }
        }

        if(Document\Service::pathExists($this->getRealFullPath())) {
            $duplicate = Document::getByPath($this->getRealFullPath());
            if ($duplicate instanceof Document  and $duplicate->getId() != $this->getId()) {
                throw new \Exception("Duplicate full path [ " . $this->getRealFullPath() . " ] - cannot save document");
            }
        }

        if(strlen($this->getRealFullPath()) > 765) {
            throw new \Exception("Full path is limited to 765 characters, reduce the length of your parent's path");
        }
    }

    /**
     * @throws \Exception
     */
    protected function update() {

        if (!$this->getKey() && $this->getId() != 1) {
            $this->delete();
            throw new \Exception("Document requires key, document with id " . $this->getId() . " deleted");
        }

        $disallowedKeysInFirstLevel = array("install","admin","webservice","plugin");
        if($this->getParentId() == 1 && in_array($this->getKey(), $disallowedKeysInFirstLevel)) {
            throw new \Exception("Key: " . $this->getKey() . " is not allowed in first level (root-level)");
        }

        // set index if null
        if($this->getIndex() === null) {
            $this->setIndex($this->getResource()->getNextIndex());
        }

        // save properties
        $this->getProperties();
        $this->getResource()->deleteAllProperties();
        if (is_array($this->getProperties()) and count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setResource(null);
                    $property->setCid($this->getId());
                    $property->setCtype("document");
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = $this->getDependencies();
        $d->clean();

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement["id"] == $this->getId() && $requirement["type"] == "document") {
                // dont't add a reference to yourself
                continue;
            }
            else {
                $d->addRequirement($requirement["id"], $requirement["type"]);
            }
        }
        $d->save();

        $this->getResource()->update();

        //set object to registry
        \Zend_Registry::set("document_" . $this->getId(), $this);
    }

    /**
     * @param $index
     */
    public function saveIndex($index) {
        $this->getResource()->saveIndex($index);
        $this->clearDependentCache();
    }

    /**
     * @param array $additionalTags
     */
    public function clearDependentCache($additionalTags = array()) {
        try {
            $tags = array("document_" . $this->getId(), "document_properties", "output");
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        }
        catch (\Exception $e) {
            \Logger::crit($e);
        }
    }

    /**
     * Returns the dependencies of the document
     *
     * @return Dependency
     */
    public function getDependencies() {

        if (!$this->dependencies) {
            $this->dependencies = Dependency::getBySourceId($this->getId(), "document");
        }
        return $this->dependencies;
    }

    /**
     * set the children of the document
     *
     * @return array
     */
    public function setChilds($childs) {
        $this->childs=$childs;
        if(is_array($childs) and count($childs>0)){
            $this->hasChilds=true;
        } else if ($childs === null) {
            $this->hasChilds = null;
        } else {
            $this->hasChilds=false;
        }
        return $this;
    }

    /**
     * Get a list of the Childs (not recursivly)
     * @param bool
     * @return array
     */
    public function getChilds($unpublished = false) {

        if ($this->childs === null) {
            $list = new Document\Listing();
            $list->setUnpublished($unpublished);
            $list->setCondition("parentId = ?", $this->getId());
            $list->setOrderKey("index");
            $list->setOrder("asc");
            $this->childs = $list->load();
        }
        return $this->childs;
    }


    /**
     * Returns true if the document has at least one child
     *
     * @return boolean
     */
    public function hasChilds() {
        if(is_bool($this->hasChilds)){
            if(($this->hasChilds and empty($this->childs)) or (!$this->hasChilds and !empty($this->childs))){
                return $this->getResource()->hasChilds();
            } else {
                return $this->hasChilds;
            }
        }
        return $this->getResource()->hasChilds();
    }

	/**
	 * Get a list of the sibling documents
	 *
	 * @param bool $unpublished
	 * @return array
	 */
	public function getSiblings($unpublished = false) {
		if ($this->siblings === null) {
			$list = new Document\Listing();
			$list->setUnpublished($unpublished);
			// string conversion because parentId could be 0
			$list->addConditionParam("parentId = ?", (string)$this->getParentId());
			$list->addConditionParam("id != ?", $this->getId());
			$list->setOrderKey("index");
			$list->setOrder("asc");
			$this->siblings = $list->load();
		}
		return $this->siblings;
	}

	/**
	 * Returns true if the document has at least one sibling
	 *
	 * @return bool
	 */
	public function hasSiblings() {
		if(is_bool($this->hasSiblings)){
			if(($this->hasSiblings and empty($this->siblings)) or (!$this->hasSiblings and !empty($this->siblings))){
				return $this->getResource()->hasSiblings();
			} else {
				return $this->hasSiblings;
			}
		}
		return $this->getResource()->hasSiblings();
	}

    /**
     * Returns true if the element is locked
     * @return string
     */
    public function getLocked(){
        if(empty($this->locked)) {
            return null;
        }
        return $this->locked;
    }

    /**
     * @param  $locked
     * @return void
     */
    public function setLocked($locked){
        $this->locked = $locked;
        return $this;
    }

    /**
     * Deletes the document
     *
     * @return void
     */
    public function delete() {

        \Pimcore::getEventManager()->trigger("document.preDelete", $this);

        // remove childs
        if ($this->hasChilds()) {
            // delete also unpublished children
            $unpublishedStatus = self::doHideUnpublished();
            self::setHideUnpublished(false);
            foreach ($this->getChilds(true) as $child) {
                $child->delete();
            }
            self::setHideUnpublished($unpublishedStatus);
        }

        // remove all properties
        $this->getResource()->deleteAllProperties();

        // remove permissions
        $this->getResource()->deleteAllPermissions();

        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        $this->getResource()->delete();

        // clear cache
        $this->clearDependentCache();

        //set object to registry
        \Zend_Registry::set("document_" . $this->getId(), null);

        \Pimcore::getEventManager()->trigger("document.postDelete", $this);
    }

    /**
     * Returns the full path of the document including the key (path+key)
     *
     * @return string
     */
    public function getFullPath() {

        // check if this document is also the site root, if so return /
        try {
            if(Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site instanceof Site) {
                    if ($site->getRootDocument()->getId() == $this->getId()) {
                        return "/";
                    }
                }
            }
        } catch (\Exception $e) {
            \Logger::error($e);
        }

        // @TODO please forgive me, this is the dirtiest hack I've ever made :(
        // if you got confused by this functionality drop me a line and I'll buy you some beers :)

        // this is for the case that a link points to a document outside of the current site
        // in this case we look for a hardlink in the current site which points to the current document
        // why this could happen: we have 2 sites, in one site there's a hardlink to the other site and on a page inside
        // the hardlink there are snippets embedded and this snippets have links pointing to a document which is also
        // inside the hardlink scope, but this is an ID link, so we cannot rewrite the link the usual way because in the
        // snippet / link we don't know anymore that whe a inside a hardlink wrapped document
        if(!\Pimcore::inAdmin() && Site::isSiteRequest() && !FrontendTool::isDocumentInCurrentSite($this)) {

            $documentService = new Document\Service();
            $parent = $this;
            while($parent) {
                if($hardlinkId = $documentService->getDocumentIdFromHardlinkInSameSite(Site::getCurrentSite(), $parent)) {
                    $hardlink = Document::getById($hardlinkId);
                    if(FrontendTool::isDocumentInCurrentSite($hardlink)) {

                        $siteRootPath = Site::getCurrentSite()->getRootPath();
                        $siteRootPath = preg_quote($siteRootPath);
                        $hardlinkPath = preg_replace("@^" . $siteRootPath . "@", "", $hardlink->getRealFullPath());

                        return preg_replace("@^" . preg_quote($parent->getRealFullPath()) . "@", $hardlinkPath, $this->getRealFullPath());
                        break;
                    }
                }
                $parent = $parent->getParent();
            }

            $config = \Pimcore\Config::getSystemConfig();
            $front = \Zend_Controller_Front::getInstance();
            $scheme = ($front->getRequest()->isSecure() ? "https" : "http") . "://";
            if($site = FrontendTool::getSiteForDocument($this)) {
                if($site->getMainDomain()) {
                    // check if current document is the root of the different site, if so, preg_replace below doesn't work, so just return /
                    if ($site->getRootDocument()->getId() == $this->getId()) {
                        return $scheme . $site->getMainDomain() . "/";
                    }
                    return $scheme . $site->getMainDomain() . preg_replace("@^" . $site->getRootPath() . "/@", "/", $this->getRealFullPath());
                }
            }

            if ($config->general->domain) {
                return $scheme . $config->general->domain . $this->getRealFullPath();
            }
        }

        $path = $this->getPath() . $this->getKey();
        return $path;
    }

    /**
     * @return integer
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @return integer
     */
    public function getId() {
        return (int) $this->id;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return integer
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * @return string
     */
    public function getPath() {

        // check for site, if so rewrite the path for output
        try {
            if(!\Pimcore::inAdmin() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site instanceof Site) {
                    if ($site->getRootDocument() instanceof Document\Page && $site->getRootDocument() !== $this) {
                        $rootPath = $site->getRootPath();
                        $rootPath = preg_quote($rootPath);
                        return preg_replace("@^" . $rootPath . "@", "", $this->path);
                    }
                }
            }
        } catch (\Exception $e) {
            \Logger::error($e);
        }

        return $this->path;
    }

    /**
     * @return string
     */
    public function getRealPath() {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getRealFullPath() {
        $path = $this->getRealPath() . $this->getKey();
        return $path;
    }

    /**
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param integer $key
     * @return void
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }


    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }


    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {
        $this->parentId = (int) $parentId;
        $this->parent = Document::getById($parentId);
        return $this;
    }

    /**
     * @param integer $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * @return integer
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * @param integer $index
     * @return void
     */
    public function setIndex($index) {
        $this->index = (int) $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param integer $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return integer
     */
    public function getUserModification() {
        return $this->userModification;
    }

    /**
     * @return integer
     */
    public function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @param integer $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = (int) $userModification;
        return $this;
    }

    /**
     * @param integer $userOwner
     * @return void
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = (int) $userOwner;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublished() {
        return $this->getPublished();
    }

    /**
     * @return boolean
     */
    public function getPublished() {
        return (bool) $this->published;
    }

    /**
     * @param integer $published
     * @return void
     */
    public function setPublished($published) {
        $this->published = (bool) $published;
        return $this;
    }

    /**
     * Get a list of properties (including the inherited)
     *
     * @return Property[]
     */
    public function getProperties() {
        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = "document_properties_" . $this->getId();
            $properties = Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getResource()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = array("document_properties" => "document_properties", $elementCacheTag => $elementCacheTag);
                Cache::save($properties, $cacheKey, $cacheTags);
            }

            $this->setProperties($properties);
        }
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return void
     */
    public function setProperties($properties) {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     * @param bool $inheritable
     * @return void
     */
    public function setProperty($name, $type, $data, $inherited = false, $inheritable = true) {

        $this->getProperties();

        $property = new Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype("document");
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->properties[$name] = $property;
        return $this;
    }

    /**
     * @return Document
     */
    public function getParent() {

        if($this->parent === null) {
            $this->setParent(Document::getById($this->getParentId()));
        }

        return $this->parent;
    }

    /**
     * @param Document $parent
     * @return void
     */
    public function setParent ($parent) {
        $this->parent = $parent;
        if($parent instanceof Document) {
            $this->parentId = $parent->getId();
        }
        return $this;
    }

    /**
     *
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();

        if(isset($this->_fulldump)) {
            // this is if we want to make a full dump of the object (eg. for a new version), including childs for recyclebin
            $blockedVars = array("dependencies", "userPermissions", "hasChilds", "versions", "scheduledTasks", "parent");
            $finalVars[] = "_fulldump";
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array("dependencies", "userPermissions", "childs", "hasChilds", "versions", "scheduledTasks", "properties", "parent");
        }


        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     *
     */
    public function __wakeup() {
        if(isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Document::getById($this->getId());
            if($originalElement) {
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getPath());
            }
        }

        if(isset($this->_fulldump) && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        if(isset($this->_fulldump)) {
            unset($this->_fulldump);
        }
    }

    /**
     *
     */
    public function removeInheritedProperties () {
        $myProperties = array();
        if($this->properties !== null) {
            foreach ($this->properties as $name => $property) {
                if(!$property->getInherited()) {
                    $myProperties[$name] = $property;
                }
            }
        }

        $this->setProperties($myProperties);
    }

    /**
     *
     */
    public function renewInheritedProperties () {
        $this->removeInheritedProperties();

        // add to registry to avoid infinite regresses in the following $this->getResource()->getProperties()
        $cacheKey = "document_" . $this->getId();
        if(!\Zend_Registry::isRegistered($cacheKey)) {
            \Zend_Registry::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getResource()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }
}
