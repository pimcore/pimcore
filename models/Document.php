<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
use Pimcore\Model\Exception\NotFoundException;
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
     * all possible types of documents
     *
     * @internal
     *
     * @var array
     */
    public static $types = ['folder', 'page', 'snippet', 'link', 'hardlink', 'email', 'newsletter', 'printpage', 'printcontainer'];

    /**
     * @var bool
     */
    private static $hideUnpublished = false;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $fullPathCache;

    /**
     * @internal
     *
     * @var int
     */
    protected $id;

    /**
     * @internal
     *
     * @var int
     */
    protected $parentId;

    /**
     * @internal
     *
     * @var self|null
     */
    protected $parent;

    /**
     * @internal
     *
     * @var string
     */
    protected string $type;

    /**
     * @internal
     *
     * @var string
     */
    protected $key;

    /**
     * @internal
     *
     * @var string
     */
    protected $path;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $index = null;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $published = true;

    /**
     * @internal
     *
     * @var int
     */
    protected $creationDate;

    /**
     * @internal
     *
     * @var int
     */
    protected $modificationDate;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $userOwner = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $userModification = null;

    /**
     * @internal
     *
     * @var array|null
     */
    protected $properties = null;

    /**
     * @internal
     *
     * @var array
     */
    protected $children = [];

    /**
     * @internal
     *
     * @var bool[]
     */
    protected $hasChildren = [];

    /**
     * @internal
     *
     * @var array
     */
    protected $siblings = [];

    /**
     * @internal
     *
     * @var bool[]
     */
    protected $hasSiblings = [];

    /**
     * enum('self','propagate') nullable
     *
     * @internal
     *
     * @var string|null
     */
    protected $locked = null;

    /**
     * @internal
     *
     * @var int
     */
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
        } catch (NotFoundException $e) {
            $doc = null;
        }

        return $doc;
    }

    /**
     * @internal
     *
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

        $id = (int)$id;
        $cacheKey = self::getCacheKey($id);

        if (!$force && \Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $document = \Pimcore\Cache\Runtime::get($cacheKey);
            if ($document && static::typeMatch($document)) {
                return $document;
            }
        }

        if ($force || !($document = \Pimcore\Cache::load($cacheKey))) {
            $document = new Document();
            try {
                $document->getDao()->getById($id);
            } catch (NotFoundException $e) {
                return null;
            }

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

        if (!$document || !static::typeMatch($document)) {
            return null;
        }

        return $document;
    }

    /**
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
        self::checkCreateData($data);
        $document->setValues($data);

        if ($save) {
            $document->save();
        }

        return $document;
    }

    /**
     * @param array $config
     *
     * @return Listing
     *
     * @throws \Exception
     */
    public static function getList(array $config = []): Listing
    {
        /** @var Listing $list */
        $list = self::getModelFactory()->build(Listing::class);
        $list->setValues($config);

        return $list;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param array $config
     *
     * @return int count
     */
    public static function getTotalCount(array $config = []): int
    {
        $list = static::getList($config);

        return $list->getTotalCount();
    }

    /**
     * {@inheritdoc}
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
                \Pimcore::getEventDispatcher()->dispatch($preEvent, DocumentEvents::PRE_UPDATE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch($preEvent, DocumentEvents::PRE_ADD);
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
                \Pimcore::getEventDispatcher()->dispatch($updateEvent, DocumentEvents::POST_UPDATE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(new DocumentEvent($this), DocumentEvents::POST_ADD);
            }

            return $this;
        } catch (\Exception $e) {
            $failureEvent = new DocumentEvent($this);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                \Pimcore::getEventDispatcher()->dispatch($failureEvent, DocumentEvents::POST_UPDATE_FAILURE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch($failureEvent, DocumentEvents::POST_ADD_FAILURE);
            }

            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    private function correctPath()
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
     * @internal
     *
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        $disallowedKeysInFirstLevel = ['install', 'admin', 'plugin'];
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
     * @internal
     *
     * @param int $index
     */
    public function saveIndex($index)
    {
        $this->getDao()->saveIndex($index);
        $this->clearDependentCache();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getLocked()
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @internal
     *
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
     * {@inheritdoc}
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new DocumentEvent($this), DocumentEvents::PRE_DELETE);

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
                /** @var Document $parent */
                $parent = \Pimcore\Cache\Runtime::get($parentCacheKey);
                if ($parent instanceof self) {
                    $parent->setChildren(null);
                }
            }
        } catch (\Exception $e) {
            $this->rollBack();
            $failureEvent = new DocumentEvent($this);
            $failureEvent->setArgument('exception', $e);
            \Pimcore::getEventDispatcher()->dispatch($failureEvent, DocumentEvents::POST_DELETE_FAILURE);
            Logger::error($e);
            throw $e;
        }

        // clear cache
        $this->clearDependentCache();

        //clear document from registry
        \Pimcore\Cache\Runtime::set(self::getCacheKey($this->getId()), null);

        \Pimcore::getEventDispatcher()->dispatch(new DocumentEvent($this), DocumentEvents::POST_DELETE);
    }

    /**
     * {@inheritdoc}
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
    private function prepareFrontendPath($path)
    {
        if (\Pimcore\Tool::isFrontend()) {
            $path = urlencode_ignore_slash($path);

            $event = new GenericEvent($this, [
                'frontendPath' => $path,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::DOCUMENT_PATH);
            $path = $event->getArgument('frontendPath');
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getRealPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getRealFullPath()
    {
        $path = $this->getRealPath() . $this->getKey();

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Returns the document index.
     *
     * @return int|null
     */
    public function getIndex(): ?int
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserModification($userModification)
    {
        $this->markFieldDirty('userModification');

        $this->userModification = (int) $userModification;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int) $userOwner;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->getPublished();
    }

    /**
     * @return bool
     */
    public function getPublished()
    {
        return (bool) $this->published;
    }

    /**
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setProperties(?array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty($name, $type, $data, $inherited = false, $inheritable = false)
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
     * {@inheritdoc}
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
     * @internal
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
     * {@inheritdoc}
     */
    public function getVersionCount(): int
    {
        return $this->versionCount ? $this->versionCount : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersionCount(?int $versionCount): ElementInterface
    {
        $this->versionCount = (int) $versionCount;

        return $this;
    }

    /**
     * @internal
     *
     * @param array $args
     *
     * @return string
     */
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
