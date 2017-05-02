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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 *
 */

namespace Pimcore\Model;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model\Document\Listing;
use Pimcore\Tool;
use Pimcore\Tool\Frontend as FrontendTool;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Document\Dao getDao()
 * @method bool __isBasedOnLatestData()
 */
class Document extends Element\AbstractElement
{
    use Element\ChildsCompatibilityTrait;

    /**
     * possible types of a document
     *
     * @var array
     */
    public static $types = ['folder', 'page', 'snippet', 'link', 'hardlink', 'email', 'newsletter', 'printpage', 'printcontainer'];

    /**
     * Add document type to the $types array. It defines additional document types available in Pimcore.
     *
     * @param $type
     */
    public static function addDocumentType($type)
    {
        if (!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }

    /**
     * @var bool
     */
    private static $hidePublished = false;

    /**
     * Set true if want to hide documents.
     *
     * @param bool $flag
     */
    public static function setHideUnpublished($flag)
    {
        self::$hidePublished = $flag;
    }

    /**
     * Checks if unpublished documents should be hidden.
     *
     * @return bool
     */
    public static function doHideUnpublished()
    {
        return self::$hidePublished;
    }

    /**
     * @var array
     */
    protected static $pathCache = [];

    /**
     * ID of the document
     *
     * @var int
     */
    public $id;

    /**
     * ID of the parent document, on root document this is null
     *
     * @var int
     */
    public $parentId;

    /**
     * The parent document.
     *
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
     * @var int
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
     * @var int
     */
    public $creationDate;

    /**
     * timestamp of modificationdate
     *
     * @var int
     */
    public $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var int
     */
    public $userOwner = 0;

    /**
     * User-ID of the user last modified the document
     *
     * @var int
     */
    public $userModification = 0;

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
     * @var bool
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
     * @var bool
     */
    public $hasSiblings;

    /**
     * Check if the document is locked.
     *
     * @var string
     */
    public $locked = null;

    /**
     * get possible types
     *
     * @return array
     */
    public static function getTypes()
    {
        return self::$types;
    }

    /**
     * Static helper to get a Document by it's path
     *
     * @param string $path
     *
     * @return Document|Document\Email|Document\Folder|Document\Hardlink|Document\Link|Document\Page|Document\Printcontainer|Document\Printpage|Document\Snippet
     */
    public static function getByPath($path)
    {
        $path = Element\Service::correctPath($path);

        if(isset(self::$pathCache[$path])) {
            return self::$pathCache[$path];
        }

        $doc = null;

        try {
            $document = new Document();
            // validate path
            if (Tool::isValidPath($path)) {
                $document->getDao()->getByPath($path);
            }

            $doc = self::getById($document->getId());
        } catch (\Exception $e) {
            Logger::debug($e->getMessage());
            $doc = null;
        }

        self::$pathCache[$path] = $doc;

        return $doc;
    }

    /**
     * Static helper to get a Document by it's ID
     *
     * @param int $id
     * @param bool $force
     *
     * @return Document|Document\Email|Document\Folder|Document\Hardlink|Document\Link|Document\Page|Document\Printcontainer|Document\Printpage|Document\Snippet|Document\Newsletter
     */
    public static function getById($id, $force = false)
    {
        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = 'document_' . $id;

        if (!$force && \Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $document = \Pimcore\Cache\Runtime::get($cacheKey);
            if ($document) {
                return $document;
            }
        }

        try {
            if ($force || !($document = \Pimcore\Cache::load($cacheKey))) {
                $document = new Document();
                $document->getDao()->getById($id);

                $className = 'Pimcore\\Model\\Document\\' . ucfirst($document->getType());

                // this is the fallback for custom document types using prefixes
                // so we need to check if the class exists first
                if (!Tool::classExists($className)) {
                    $oldStyleClass = 'Document_' . ucfirst($document->getType());
                    if (Tool::classExists($oldStyleClass)) {
                        $className = $oldStyleClass;
                    }
                }

                $document = \Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
                \Pimcore\Cache\Runtime::set($cacheKey, $document);
                $document->getDao()->getById($id);
                $document->__setDataVersionTimestamp($document->getModificationDate());

                \Pimcore\Cache::save($document, $cacheKey);
            } else {
                \Pimcore\Cache\Runtime::set($cacheKey, $document);
            }
        } catch (\Exception $e) {
            Logger::warning($e->getMessage());

            return null;
        }

        if (!$document) {
            return null;
        }

        return $document;
    }

    /**
     * Static helper to quickly create a new document
     *
     * @param int $parentId
     * @param array $data
     * @param bool $save
     *
     * @return Document
     */
    public static function create($parentId, $data = [], $save = true)
    {
        $document = new static();
        $document->setParentId($parentId);

        foreach ($data as $key => $value) {
            $document->setValue($key, $value);
        }

        if ($save) {
            $document->save();
        }

        return $document;
    }

    /**
     * Returns the documents list instance.
     *
     * @param array $config
     *
     * @return Listing
     *
     * @throws \Exception
     */
    public static function getList($config = [])
    {
        if (is_array($config)) {
            $listClass = 'Pimcore\\Model\\Document\\Listing';
            $list = \Pimcore::getContainer()->get('pimcore.model.factory')->build($listClass);
            $list->setValues($config);
            $list->load();

            return $list;
        }

        throw new \Exception('Unable to initiate list class - class not found or invalid configuration');
    }

    /**
     * Get total count of documents.
     *
     * @param array $config
     *
     * @return int count
     */
    public static function getTotalCount($config = [])
    {
        if (is_array($config)) {
            $listClass = 'Pimcore\\Model\\Document\\Listing';
            $list = \Pimcore::getContainer()->get('pimcore.model.factory')->build($listClass);
            $list->setValues($config);
            $count = $list->getTotalCount();

            return $count;
        }
    }

    /**
     * Save the document.
     *
     * @return Document
     *
     * @throws \Exception
     */
    public function save()
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_UPDATE, new DocumentEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_ADD, new DocumentEvent($this));
        }

        $this->correctPath();

        // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
        // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
        // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
        $maxRetries = 5;
        for ($retries=0; $retries < $maxRetries; $retries++) {
            $this->beginTransaction();

            try {
                // set date
                $this->setModificationDate(time());

                if (!$this->getCreationDate()) {
                    $this->setCreationDate(time());
                }

                if (!$isUpdate) {
                    $this->getDao()->create();
                }

                // get the old path from the database before the update is done
                $oldPath = null;
                if ($isUpdate) {
                    $oldPath = $this->getDao()->getCurrentFullPath();
                }

                $this->update();

                // if the old path is different from the new path, update all children
                $updatedChildren = [];
                if ($oldPath && $oldPath != $this->getRealFullPath()) {
                    $this->getDao()->updateWorkspaces();
                    $updatedChildren = $this->getDao()->updateChildsPaths($oldPath);
                }

                $this->commit();

                break; // transaction was successfully completed, so we cancel the loop here -> no restart required
            } catch (\Exception $e) {
                try {
                    $this->rollBack();
                } catch (\Exception $er) {
                    // PDO adapter throws exceptions if rollback fails
                    Logger::error($er);
                }

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if ($retries < ($maxRetries - 1)) {
                    $run = $retries + 1;
                    $waitTime = 100000; // microseconds
                    Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    throw $e;
                }
            }
        }

        $additionalTags = [];
        if (isset($updatedChildren) && is_array($updatedChildren)) {
            foreach ($updatedChildren as $documentId) {
                $tag = 'document_' . $documentId;
                $additionalTags[] = $tag;

                // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                \Pimcore\Cache\Runtime::set($tag, null);
            }
        }
        $this->clearDependentCache($additionalTags);

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_UPDATE, new DocumentEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_ADD, new DocumentEvent($this));
        }

        return $this;
    }

    /**
     * Validate the document path.
     *
     * @throws \Exception
     */
    public function correctPath()
    {
        // set path
        if ($this->getId() != 1) { // not for the root node

            // check for a valid key, home has no key, so omit the check
            if (!Element\Service::isValidKey($this->getKey(), 'document')) {
                throw new \Exception('invalid key for document with id [ ' . $this->getId() . ' ] key is: [' . $this->getKey() . ']');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            $parent = Document::getById($this->getParentId());
            if ($parent) {
                // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
                // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
                $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath() . '/'));
            } else {
                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath('/');
            }

            if (strlen($this->getKey()) < 1) {
                throw new \Exception('Document requires key, generated key automatically');
            }
        } elseif ($this->getId() == 1) {
            // some data in root node should always be the same
            $this->setParentId(0);
            $this->setPath('/');
            $this->setKey('');
            $this->setType('page');
        }

        if (Document\Service::pathExists($this->getRealFullPath())) {
            $duplicate = Document::getByPath($this->getRealFullPath());
            if ($duplicate instanceof Document and $duplicate->getId() != $this->getId()) {
                throw new \Exception('Duplicate full path [ ' . $this->getRealFullPath() . ' ] - cannot save document');
            }
        }

        if (strlen($this->getRealFullPath()) > 765) {
            throw new \Exception("Full path is limited to 765 characters, reduce the length of your parent's path");
        }
    }

    /**
     * @throws \Exception
     */
    protected function update()
    {
        $disallowedKeysInFirstLevel = ['install', 'admin', 'webservice', 'plugin'];
        if ($this->getParentId() == 1 && in_array($this->getKey(), $disallowedKeysInFirstLevel)) {
            throw new \Exception('Key: ' . $this->getKey() . ' is not allowed in first level (root-level)');
        }

        // set index if null
        if ($this->getIndex() === null) {
            $this->setIndex($this->getDao()->getNextIndex());
        }

        // save properties
        $this->getProperties();
        $this->getDao()->deleteAllProperties();
        if (is_array($this->getProperties()) and count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('document');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = $this->getDependencies();
        $d->clean();

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement['id'] == $this->getId() && $requirement['type'] == 'document') {
                // dont't add a reference to yourself
                continue;
            } else {
                $d->addRequirement($requirement['id'], $requirement['type']);
            }
        }
        $d->save();

        $this->getDao()->update();

        //set object to registry
        \Pimcore\Cache\Runtime::set('document_' . $this->getId(), $this);
    }

    /**
     * Update the document index.
     *
     * @param int $index
     */
    public function saveIndex($index)
    {
        $this->getDao()->saveIndex($index);
        $this->clearDependentCache();
    }

    /**
     * Clear the cache related to the document.
     *
     * @param array $additionalTags
     */
    public function clearDependentCache($additionalTags = [])
    {
        try {
            $tags = ['document_' . $this->getId(), 'document_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            \Pimcore\Cache::clearTags($tags);
        } catch (\Exception $e) {
            Logger::crit($e);
        }
    }

    /**
     * Returns the dependencies of the document
     *
     * @return Dependency
     */
    public function getDependencies()
    {
        if (!$this->dependencies) {
            $this->dependencies = Dependency::getBySourceId($this->getId(), 'document');
        }

        return $this->dependencies;
    }

    /**
     * set the children of the document
     *
     * @param $children
     *
     * @return array
     *
     * @todo: replace and with &&
     */
    public function setChildren($children)
    {
        $this->childs=$children;
        if (is_array($children) and count($children > 0)) {
            $this->hasChilds=true;
        } elseif ($children === null) {
            $this->hasChilds = null;
        } else {
            $this->hasChilds=false;
        }

        return $this;
    }

    /**
     * Get a list of the Childs (not recursivly)
     *
     * @param bool
     *
     * @return array
     */
    public function getChildren($unpublished = false)
    {
        if ($this->childs === null) {
            $list = new Document\Listing();
            $list->setUnpublished($unpublished);
            $list->setCondition('parentId = ?', $this->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');
            $this->childs = $list->load();
        }

        return $this->childs;
    }

    /**
     * Returns true if the document has at least one child
     *
     * @return bool
     */
    public function hasChildren()
    {
        if (is_bool($this->hasChilds)) {
            if (($this->hasChilds and empty($this->childs)) or (!$this->hasChilds and !empty($this->childs))) {
                return $this->getDao()->hasChildren();
            } else {
                return $this->hasChilds;
            }
        }

        return $this->getDao()->hasChildren();
    }

    /**
     * Get a list of the sibling documents
     *
     * @param bool $unpublished
     *
     * @return array
     */
    public function getSiblings($unpublished = false)
    {
        if ($this->siblings === null) {
            $list = new Document\Listing();
            $list->setUnpublished($unpublished);
            // string conversion because parentId could be 0
            $list->addConditionParam('parentId = ?', (string)$this->getParentId());
            $list->addConditionParam('id != ?', $this->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');
            $this->siblings = $list->load();
        }

        return $this->siblings;
    }

    /**
     * Returns true if the document has at least one sibling
     *
     * @return bool
     */
    public function hasSiblings()
    {
        if (is_bool($this->hasSiblings)) {
            if (($this->hasSiblings and empty($this->siblings)) or (!$this->hasSiblings and !empty($this->siblings))) {
                return $this->getDao()->hasSiblings();
            } else {
                return $this->hasSiblings;
            }
        }

        return $this->getDao()->hasSiblings();
    }

    /**
     * Returns true if the element is locked
     *
     * @return string
     */
    public function getLocked()
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

    /**
     * Mark the document as locked.
     *
     * @param  $locked
     *
     * @return Document
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Deletes the document
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_DELETE, new DocumentEvent($this));

        // remove childs
        if ($this->hasChildren()) {
            // delete also unpublished children
            $unpublishedStatus = self::doHideUnpublished();
            self::setHideUnpublished(false);
            foreach ($this->getChildren(true) as $child) {
                $child->delete();
            }
            self::setHideUnpublished($unpublishedStatus);
        }

        // remove all properties
        $this->getDao()->deleteAllProperties();

        // remove permissions
        $this->getDao()->deleteAllPermissions();

        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        // remove translations
        $service = new Document\Service;
        $service->removeTranslation($this);

        $this->getDao()->delete();

        // clear cache
        $this->clearDependentCache();

        //set object to registry
        \Pimcore\Cache\Runtime::set('document_' . $this->getId(), null);

        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_DELETE, new DocumentEvent($this));
    }

    /**
     * Returns the full path of the document including the key (path+key)
     *
     * @return string
     */
    public function getFullPath()
    {

        // check if this document is also the site root, if so return /
        try {
            if (\Pimcore\Tool::isFrontend() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site instanceof Site) {
                    if ($site->getRootDocument()->getId() == $this->getId()) {
                        $link = $this->prepareFrontendPath('/');

                        return $link;
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error($e);
        }

        // @TODO please forgive me, this is the dirtiest hack I've ever made :(
        // if you got confused by this functionality drop me a line and I'll buy you some beers :)

        // this is for the case that a link points to a document outside of the current site
        // in this case we look for a hardlink in the current site which points to the current document
        // why this could happen: we have 2 sites, in one site there's a hardlink to the other site and on a page inside
        // the hardlink there are snippets embedded and this snippets have links pointing to a document which is also
        // inside the hardlink scope, but this is an ID link, so we cannot rewrite the link the usual way because in the
        // snippet / link we don't know anymore that whe a inside a hardlink wrapped document
        if (\Pimcore\Tool::isFrontend() && Site::isSiteRequest() && !FrontendTool::isDocumentInCurrentSite($this)) {
            $documentService = new Document\Service();
            $parent = $this;
            while ($parent) {
                if ($hardlinkId = $documentService->getDocumentIdFromHardlinkInSameSite(Site::getCurrentSite(), $parent)) {
                    $hardlink = Document::getById($hardlinkId);
                    if (FrontendTool::isDocumentInCurrentSite($hardlink)) {
                        $siteRootPath = Site::getCurrentSite()->getRootPath();
                        $siteRootPath = preg_quote($siteRootPath);
                        $hardlinkPath = preg_replace('@^' . $siteRootPath . '@', '', $hardlink->getRealFullPath());

                        $link = preg_replace('@^' . preg_quote($parent->getRealFullPath()) . '@', $hardlinkPath, $this->getRealFullPath());
                        $link = $this->prepareFrontendPath($link);

                        return $link;
                    }
                }
                $parent = $parent->getParent();
            }

            $config = \Pimcore\Config::getSystemConfig();

            // TODO using the container directly is discouraged, maybe we find a better way (e.g. moving this into a service)?
            $request = \Pimcore::getContainer()->get('pimcore.http.request_helper')->getRequest();
            $scheme  = $request->getScheme() . '://';

            /** @var Site $site */
            if ($site = FrontendTool::getSiteForDocument($this)) {
                if ($site->getMainDomain()) {
                    // check if current document is the root of the different site, if so, preg_replace below doesn't work, so just return /
                    if ($site->getRootDocument()->getId() == $this->getId()) {
                        $link = $scheme . $site->getMainDomain() . '/';
                        $link = $this->prepareFrontendPath($link);

                        return $link;
                    }
                    $link = $scheme . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/@', '/', $this->getRealFullPath());
                    $link = $this->prepareFrontendPath($link);

                    return $link;
                }
            }

            if ($config->general->domain) {
                $link = $scheme . $config->general->domain . $this->getRealFullPath();
                $link = $this->prepareFrontendPath($link);

                return $link;
            }
        }

        $path = $this->getPath() . $this->getKey();
        $path = $this->prepareFrontendPath($path);

        return $path;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    protected function prepareFrontendPath($path)
    {
        if (\Pimcore\Tool::isFrontend()) {
            $path = urlencode_ignore_slash($path);

            $event = new GenericEvent($this, [
                'frontendPath' => $path
            ]);
            \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::DOCUMENT_PATH, $event);
            $path = $event->getArgument('frontendPath');
        }

        return $path;
    }

    /**
     * Returns the document creation date.
     *
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Returns the document id.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * Returns the document key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Return the document modification date.
     *
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Returns the id of the parent document.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Returns the document path.
     *
     * @return string
     */
    public function getPath()
    {

        // check for site, if so rewrite the path for output
        try {
            if (\Pimcore\Tool::isFrontend() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site instanceof Site) {
                    if ($site->getRootDocument() instanceof Document\Page && $site->getRootDocument() !== $this) {
                        $rootPath = $site->getRootPath();
                        $rootPath = preg_quote($rootPath);
                        $link = preg_replace('@^' . $rootPath . '@', '', $this->path);

                        return $link;
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error($e);
        }

        return $this->path;
    }

    /**
     * Returns the real document path.
     *
     * @return string
     */
    public function getRealPath()
    {
        return $this->path;
    }

    /**
     * Returns the full real path of the document.
     *
     * @return string
     */
    public function getRealFullPath()
    {
        $path = $this->getRealPath() . $this->getKey();

        return $path;
    }

    /**
     * Set the creation date of the document.
     *
     * @param int $creationDate
     *
     * @return Document
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * Set the id of the document.
     *
     * @param int $id
     *
     * @return Document
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * Set the document key.
     *
     * @param int $key
     *
     * @return Document
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set the document modification date.
     *
     * @param int $modificationDate
     *
     * @return Document
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * Set the parent id of the document.
     *
     * @param int $parentId
     *
     * @return Document
     */
    public function setParentId($parentId)
    {
        $this->parentId = (int) $parentId;
        $this->parent = null;

        return $this;
    }

    /**
     * Set the document path.
     *
     * @param string $path
     *
     * @return Document
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Returns the document index.
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set the document index.
     *
     * @param int $index
     *
     * @return Document
     */
    public function setIndex($index)
    {
        $this->index = (int) $index;

        return $this;
    }

    /**
     * Returns the document type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the document type.
     *
     * @param int $type
     *
     * @return Document
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns id of the user last modified the document.
     *
     * @return int
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * Returns the id of the owner user.
     *
     * @return int
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * Set id of the user last modified the document.
     *
     * @param int $userModification
     *
     * @return Document
     */
    public function setUserModification($userModification)
    {
        $this->userModification = (int) $userModification;

        return $this;
    }

    /**
     * Set the id of the owner user.
     *
     * @param int $userOwner
     *
     * @return Document
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int) $userOwner;

        return $this;
    }

    /**
     * Checks if the document is published.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->getPublished();
    }

    /**
     * Checks if the document is published.
     *
     * @return bool
     */
    public function getPublished()
    {
        return (bool) $this->published;
    }

    /**
     * Set the publish status of the document.
     *
     * @param int $published
     *
     * @return Document
     */
    public function setPublished($published)
    {
        $this->published = (bool) $published;

        return $this;
    }

    /**
     * Get a list of properties (including the inherited)
     *
     * @return Property[]
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = 'document_properties_' . $this->getId();
            $properties = \Pimcore\Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getDao()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = ['document_properties' => 'document_properties', $elementCacheTag => $elementCacheTag];
                \Pimcore\Cache::save($properties, $cacheKey, $cacheTags);
            }

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    /**
     * Set document properties.
     *
     * @param array $properties
     *
     * @return Document
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Set the document property.
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     * @param bool $inheritable
     *
     * @return Document
     */
    public function setProperty($name, $type, $data, $inherited = false, $inheritable = true)
    {
        $this->getProperties();

        $property = new Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype('document');
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->properties[$name] = $property;

        return $this;
    }

    /**
     * Returns the parent document instance.
     *
     * @return Document
     */
    public function getParent()
    {
        if ($this->parent === null) {
            $this->setParent(Document::getById($this->getParentId()));
        }

        return $this->parent;
    }

    /**
     * Set the parent document instance.
     *
     * @param Document $parent
     *
     * @return Document
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        if ($parent instanceof Document) {
            $this->parentId = $parent->getId();
        }

        return $this;
    }

    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        if (isset($this->_fulldump)) {
            // this is if we want to make a full dump of the object (eg. for a new version), including childs for recyclebin
            $blockedVars = ['dependencies', 'userPermissions', 'hasChilds', 'versions', 'scheduledTasks', 'parent'];
            $finalVars[] = '_fulldump';
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = ['dependencies', 'userPermissions', 'childs', 'hasChilds', 'versions', 'scheduledTasks', 'properties', 'parent'];
        }

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function __wakeup()
    {
        if (isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Document::getById($this->getId());
            if ($originalElement) {
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getRealPath());
            }
        }

        if (isset($this->_fulldump) && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        if (isset($this->_fulldump)) {
            unset($this->_fulldump);
        }
    }

    /**
     *  Removes all inherited properties.
     */
    public function removeInheritedProperties()
    {
        $myProperties = [];
        if ($this->properties !== null) {
            foreach ($this->properties as $name => $property) {
                if (!$property->getInherited()) {
                    $myProperties[$name] = $property;
                }
            }
        }

        $this->setProperties($myProperties);
    }

    /**
     * Renews all inherited properties.
     */
    public function renewInheritedProperties()
    {
        $this->removeInheritedProperties();

        // add to registry to avoid infinite regresses in the following $this->getDao()->getProperties()
        $cacheKey = 'document_' . $this->getId();
        if (!\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            \Pimcore\Cache\Runtime::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getDao()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }

    /**
     * returns true if document should be rendered with legacy stack
     *
     * @return bool
     */
    public function doRenderWithLegacyStack()
    {
        return false;
    }
}
