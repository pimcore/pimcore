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

use Doctrine\DBAL\Exception\DeadlockException;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Document\Listing;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;
use Pimcore\Tool\Frontend as FrontendTool;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Document\Dao getDao()
 * @method bool __isBasedOnLatestData()
 * @method int getChildAmount($user = null)
 * @method string getCurrentFullPath()
 */
class Document extends Element\AbstractElement
{
    /**
     * possible types of a document
     *
     * @var array
     */
    public static $types = ['folder', 'page', 'snippet', 'link', 'hardlink', 'email', 'newsletter', 'printpage', 'printcontainer'];

    /**
     * @var bool
     */
    private static $hideUnpublished = false;

    /**
     * @var string|null
     */
    protected $fullPathCache;

    /**
     * ID of the document
     *
     * @var int
     */
    protected $id;

    /**
     * ID of the parent document, on root document this is null
     *
     * @var int
     */
    protected $parentId;

    /**
     * The parent document.
     *
     * @var Document|null
     */
    protected $parent;

    /**
     * Type of the document as string (enum)
     * Possible values: page,snippet,link,folder
     *
     * @var string
     */
    protected $type;

    /**
     * Filename/Key of the document
     *
     * @var string
     */
    protected $key;

    /**
     * Path to the document, not conaining the key (the full path of the parent document)
     *
     * @var string
     */
    protected $path;

    /**
     * Sorter index in the tree, can also be used for generating a navigation and so on
     *
     * @var int
     */
    protected $index;

    /**
     * published or not
     *
     * @var bool
     */
    protected $published = true;

    /**
     * timestamp of creationdate
     *
     * @var int
     */
    protected $creationDate;

    /**
     * timestamp of modificationdate
     *
     * @var int
     */
    protected $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var int
     */
    protected $userOwner;

    /**
     * User-ID of the user last modified the document
     *
     * @var int
     */
    protected $userModification;

    /**
     * List of Property, concerning the folder
     *
     * @var array|null
     */
    protected $properties = null;

    /**
     * Contains a list of child-documents
     *
     * @var array
     */
    protected $children = [];

    /**
     * Indicator of document has children or not.
     *
     * @var bool[]
     */
    protected $hasChildren = [];

    /**
     * Contains a list of sibling documents
     *
     * @var array
     */
    protected $siblings = [];

    /**
     * Indicator if document has siblings or not
     *
     * @var bool[]
     */
    protected $hasSiblings = [];

    /**
     * enum('self','propagate') nullable
     *
     * @var string|null
     */
    protected $locked = null;

    /** @var int */
    protected $versionCount;

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
     * @param bool $force
     *
     * @return static|null
     */
    public static function getByPath($path, $force = false)
    {
        $path = Element\Service::correctPath($path);

        $cacheKey = 'document_path_' . md5($path);

        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            return \Pimcore\Cache\Runtime::get($cacheKey);
        }

        $doc = null;

        try {
            $helperDoc = new Document();
            $helperDoc->getDao()->getByPath($path);
            $doc = static::getById($helperDoc->getId(), $force);
            \Pimcore\Cache\Runtime::set($cacheKey, $doc);
        } catch (\Exception $e) {
            $doc = null;
        }

        return $doc;
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    protected static function typeMatch(Document $document)
    {
        $staticType = get_called_class();
        if ($staticType != Document::class) {
            if (!$document instanceof $staticType) {
                return false;
            }
        }

        return true;
    }

    /**
     * Static helper to get a Document by it's ID
     *
     * @param int $id
     * @param bool $force
     *
     * @return static|null
     */
    public static function getById($id, $force = false)
    {
        if (!is_numeric($id) || $id < 1) {
            return null;
        }

        $id = intval($id);
        $cacheKey = self::getCacheKey($id);

        if (!$force && \Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $document = \Pimcore\Cache\Runtime::get($cacheKey);
            if ($document && static::typeMatch($document)) {
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

                /** @var Document $document */
                $document = self::getModelFactory()->build($className);
                \Pimcore\Cache\Runtime::set($cacheKey, $document);

                $document->getDao()->getById($id);
                $document->__setDataVersionTimestamp($document->getModificationDate());

                $document->resetDirtyMap();

                \Pimcore\Cache::save($document, $cacheKey);
            } else {
                \Pimcore\Cache\Runtime::set($cacheKey, $document);
            }
        } catch (\Exception $e) {
            return null;
        }

        if (!$document || !static::typeMatch($document)) {
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
     * @return static
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
            /** @var Listing $list */
            $list = self::getModelFactory()->build(Listing::class);
            $list->setValues($config);

            return $list;
        }

        throw new \Exception('Unable to initiate list class - please provide valid configuration array');
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
        $list = static::getList($config);
        $count = $list->getTotalCount();

        return $count;
    }

    /**
     * @return Document
     *
     * @throws \Exception
     */
    public function save()
    {
        $isUpdate = false;

        try {
            // additional parameters (e.g. "versionNote" for the version note)
            $params = [];
            if (func_num_args() && is_array(func_get_arg(0))) {
                $params = func_get_arg(0);
            }

            $preEvent = new DocumentEvent($this, $params);
            if ($this->getId()) {
                $isUpdate = true;
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_UPDATE, $preEvent);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_ADD, $preEvent);
            }

            $params = $preEvent->getArguments();

            $this->correctPath();
            $differentOldPath = null;

            // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
            // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
            // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                $this->beginTransaction();

                try {
                    $this->updateModificationInfos();

                    if (!$isUpdate) {
                        $this->getDao()->create();
                    }

                    // get the old path from the database before the update is done
                    $oldPath = null;
                    if ($isUpdate) {
                        $oldPath = $this->getDao()->getCurrentFullPath();
                    }

                    $this->update($params);

                    // if the old path is different from the new path, update all children
                    $updatedChildren = [];
                    if ($oldPath && $oldPath != $this->getRealFullPath()) {
                        $differentOldPath = $oldPath;
                        $this->getDao()->updateWorkspaces();
                        $updatedChildren = $this->getDao()->updateChildPaths($oldPath);
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
                    if ($e instanceof DeadlockException && $retries < ($maxRetries - 1)) {
                        $run = $retries + 1;
                        $waitTime = rand(1, 5) * 100000; // microseconds
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
                $updateEvent = new DocumentEvent($this);
                if ($differentOldPath) {
                    $updateEvent->setArgument('oldPath', $differentOldPath);
                }
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_UPDATE, $updateEvent);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_ADD, new DocumentEvent($this));
            }

            return $this;
        } catch (\Exception $e) {
            $failureEvent = new DocumentEvent($this);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_UPDATE_FAILURE, $failureEvent);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_ADD_FAILURE, $failureEvent);
            }

            throw $e;
        }
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
            if ($duplicate instanceof Document && $duplicate->getId() != $this->getId()) {
                throw new \Exception('Duplicate full path [ ' . $this->getRealFullPath() . ' ] - cannot save document');
            }
        }

        $this->validatePathLength();
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
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
        if (is_array($this->getProperties()) && count($this->getProperties()) > 0) {
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
        $d = new Dependency();
        $d->setSourceType('document');
        $d->setSourceId($this->getId());

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

        //set document to registry
        \Pimcore\Cache\Runtime::set(self::getCacheKey($this->getId()), $this);
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
            $tags = [$this->getCacheTag(), 'document_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            \Pimcore\Cache::clearTags($tags);
        } catch (\Exception $e) {
            Logger::crit($e);
        }
    }

    /**
     * set the children of the document
     *
     * @param self[] $children
     * @param bool $includingUnpublished
     *
     * @return $this
     */
    public function setChildren($children, $includingUnpublished = false)
    {
        if (empty($children)) {
            // unset all cached children
            $this->hasChildren = [];
            $this->children = [];
        } elseif (is_array($children)) {
            $cacheKey = $this->getListingCacheKey([$includingUnpublished]);
            $this->children[$cacheKey] = $children;
            $this->hasChildren[$cacheKey] = (bool) count($children);
        }

        return $this;
    }

    /**
     * Get a list of the children (not recursivly)
     *
     * @param bool $includingUnpublished
     *
     * @return self[]
     */
    public function getChildren($includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->children[$cacheKey])) {
            $list = new Document\Listing();
            $list->setUnpublished($includingUnpublished);
            $list->setCondition('parentId = ?', $this->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');
            $this->children[$cacheKey] = $list->load();
        }

        return $this->children[$cacheKey];
    }

    /**
     * Returns true if the document has at least one child
     *
     * @param bool $includingUnpublished
     *
     * @return bool
     */
    public function hasChildren($includingUnpublished = null)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (isset($this->hasChildren[$cacheKey])) {
            return $this->hasChildren[$cacheKey];
        }

        return $this->hasChildren[$cacheKey] = $this->getDao()->hasChildren($includingUnpublished);
    }

    /**
     * Get a list of the sibling documents
     *
     * @param bool $includingUnpublished
     *
     * @return array
     */
    public function getSiblings($includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->siblings[$cacheKey])) {
            $list = new Document\Listing();
            $list->setUnpublished($includingUnpublished);
            // string conversion because parentId could be 0
            $list->addConditionParam('parentId = ?', (string)$this->getParentId());
            $list->addConditionParam('id != ?', $this->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');
            $this->siblings[$cacheKey] = $list->load();
            $this->hasSiblings[$cacheKey] = (bool) count($this->siblings[$cacheKey]);
        }

        return $this->siblings[$cacheKey];
    }

    /**
     * Returns true if the document has at least one sibling
     *
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasSiblings($includingUnpublished = null)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (isset($this->hasSiblings[$cacheKey])) {
            return $this->hasSiblings[$cacheKey];
        }

        return $this->hasSiblings[$cacheKey] = $this->getDao()->hasSiblings($includingUnpublished);
    }

    /**
     * enum('self','propagate') nullable
     *
     * @return string|null
     */
    public function getLocked()
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

    /**
     * enum('self','propagate') nullable
     *
     * @param string|null $locked
     *
     * @return Document
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function doDelete()
    {
        // remove children
        if ($this->hasChildren()) {
            // delete also unpublished children
            $unpublishedStatus = self::doHideUnpublished();
            self::setHideUnpublished(false);
            foreach ($this->getChildren(true) as $child) {
                if (!$child instanceof WrapperInterface) {
                    $child->delete();
                }
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
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRE_DELETE, new DocumentEvent($this));

        $this->beginTransaction();

        try {
            if ($this->getId() == 1) {
                throw new \Exception('root-node cannot be deleted');
            }

            $this->doDelete();
            $this->getDao()->delete();

            $this->commit();

            //clear parent data from registry
            $parentCacheKey = self::getCacheKey($this->getParentId());
            if (\Pimcore\Cache\Runtime::isRegistered($parentCacheKey)) {
                /** @var Document $parent * */
                $parent = \Pimcore\Cache\Runtime::get($parentCacheKey);
                if ($parent instanceof self) {
                    $parent->setChildren(null);
                }
            }
        } catch (\Exception $e) {
            $this->rollBack();
            $failureEvent = new DocumentEvent($this);
            $failureEvent->setArgument('exception', $e);
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_DELETE_FAILURE, $failureEvent);
            Logger::error($e);
            throw $e;
        }

        // clear cache
        $this->clearDependentCache();

        //clear document from registry
        \Pimcore\Cache\Runtime::set(self::getCacheKey($this->getId()), null);

        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::POST_DELETE, new DocumentEvent($this));
    }

    /**
     * Returns the frontend path to the document respecting the current site and pretty-URLs
     *
     * @param bool $force
     *
     * @return string
     */
    public function getFullPath(bool $force = false)
    {
        $link = $force ? null : $this->fullPathCache;

        // check if this document is also the site root, if so return /
        try {
            if (!$link && \Pimcore\Tool::isFrontend() && Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site instanceof Site) {
                    if ($site->getRootDocument()->getId() == $this->getId()) {
                        $link = '/';
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error($e);
        }

        $requestStack = \Pimcore::getContainer()->get('request_stack');
        $masterRequest = $requestStack->getMasterRequest();

        // @TODO please forgive me, this is the dirtiest hack I've ever made :(
        // if you got confused by this functionality drop me a line and I'll buy you some beers :)

        // this is for the case that a link points to a document outside of the current site
        // in this case we look for a hardlink in the current site which points to the current document
        // why this could happen: we have 2 sites, in one site there's a hardlink to the other site and on a page inside
        // the hardlink there are snippets embedded and this snippets have links pointing to a document which is also
        // inside the hardlink scope, but this is an ID link, so we cannot rewrite the link the usual way because in the
        // snippet / link we don't know anymore that whe a inside a hardlink wrapped document
        if (!$link && \Pimcore\Tool::isFrontend() && Site::isSiteRequest() && !FrontendTool::isDocumentInCurrentSite($this)) {
            if ($masterRequest && ($masterDocument = $masterRequest->get(DynamicRouter::CONTENT_KEY))) {
                if ($masterDocument instanceof WrapperInterface) {
                    $hardlinkPath = '';
                    $hardlink = $masterDocument->getHardLinkSource();
                    $hardlinkTarget = $hardlink->getSourceDocument();

                    if ($hardlinkTarget) {
                        $hardlinkPath = preg_replace('@^' . preg_quote(Site::getCurrentSite()->getRootPath(), '@') . '@', '', $hardlink->getRealFullPath());

                        $link = preg_replace('@^' . preg_quote($hardlinkTarget->getRealFullPath(), '@') . '@',
                            $hardlinkPath, $this->getRealFullPath());
                    }

                    if (strpos($this->getRealFullPath(), Site::getCurrentSite()->getRootDocument()->getRealFullPath()) === false && strpos($link, $hardlinkPath) === false) {
                        $link = null;
                    }
                }
            }

            if (!$link) {
                $config = \Pimcore\Config::getSystemConfiguration('general');
                $request = $requestStack->getCurrentRequest();
                $scheme = 'http://';
                if ($request) {
                    $scheme = $request->getScheme() . '://';
                }

                /** @var Site $site */
                if ($site = FrontendTool::getSiteForDocument($this)) {
                    if ($site->getMainDomain()) {
                        // check if current document is the root of the different site, if so, preg_replace below doesn't work, so just return /
                        if ($site->getRootDocument()->getId() == $this->getId()) {
                            $link = $scheme . $site->getMainDomain() . '/';
                        } else {
                            $link = $scheme . $site->getMainDomain() .
                                preg_replace('@^' . $site->getRootPath() . '/@', '/', $this->getRealFullPath());
                        }
                    }
                }

                if (!$link && !empty($config['domain']) && !($this instanceof WrapperInterface)) {
                    $link = $scheme . $config['domain'] . $this->getRealFullPath();
                }
            }
        }

        if (!$link) {
            $link = $this->getPath() . $this->getKey();
        }

        if ($masterRequest) {
            // caching should only be done when master request is available as it is done for performance reasons
            // of the web frontend, without a request object there's no need to cache anything
            // for details also see https://github.com/pimcore/pimcore/issues/5707
            $this->fullPathCache = $link;
        }

        $link = $this->prepareFrontendPath($link);

        return $link;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function prepareFrontendPath($path)
    {
        if (\Pimcore\Tool::isFrontend()) {
            $path = urlencode_ignore_slash($path);

            $event = new GenericEvent($this, [
                'frontendPath' => $path,
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
                        $rootPath = preg_quote($rootPath, '@');
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
     * Returns the internal real full path of the document. (not for frontend use!)
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
     * @param string $key
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
        $this->markFieldDirty('modificationDate');

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
        $this->siblings = [];
        $this->hasSiblings = [];

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
     * @param string $type
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
        $this->markFieldDirty('userModification');

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
     * @param Property[] $properties
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
        $parentVars = parent::__sleep();
        $blockedVars = ['hasChildren', 'versions', 'scheduledTasks', 'parent', 'fullPathCache'];

        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array_merge($blockedVars, ['children', 'properties']);
        }

        return array_diff($parentVars, $blockedVars);
    }

    public function __wakeup()
    {
        if ($this->isInDumpState()) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Document::getById($this->getId());
            if ($originalElement) {
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getRealPath());
            }
        }

        if ($this->isInDumpState() && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        $this->setInDumpState(false);
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
        $cacheKey = self::getCacheKey($this->getId());
        if (!\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            \Pimcore\Cache\Runtime::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getDao()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }

    /**
     * Add document type to the $types array. It defines additional document types available in Pimcore.
     *
     * @param string $type
     */
    public static function addDocumentType($type)
    {
        if (!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }

    /**
     * Set true if want to hide documents.
     *
     * @param bool $hideUnpublished
     */
    public static function setHideUnpublished($hideUnpublished)
    {
        self::$hideUnpublished = $hideUnpublished;
    }

    /**
     * Checks if unpublished documents should be hidden.
     *
     * @return bool
     */
    public static function doHideUnpublished()
    {
        return self::$hideUnpublished;
    }

    /**
     * @return int
     */
    public function getVersionCount(): int
    {
        return $this->versionCount ? $this->versionCount : 0;
    }

    /**
     * @param int|null $versionCount
     *
     * @return Document
     */
    public function setVersionCount(?int $versionCount): ElementInterface
    {
        $this->versionCount = (int) $versionCount;

        return $this;
    }

    protected function getListingCacheKey(array $args = [])
    {
        $unpublished = (bool)($args[0] ?? false);
        $cacheKey = (string)$unpublished;

        return $cacheKey;
    }

    public function __clone()
    {
        parent::__clone();
        $this->parent = null;
        $this->hasSiblings = [];
        $this->siblings = [];
        $this->fullPathCache = null;
    }
}
