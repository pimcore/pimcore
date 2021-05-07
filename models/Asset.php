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
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToMoveFile;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\Asset\Listing;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\DataDefinitionInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Mime\MimeTypes;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 * @method bool __isBasedOnLatestData()
 * @method int getChildAmount($user = null)
 * @method string|null getCurrentFullPath()
 */
class Asset extends Element\AbstractElement
{
    use TemporaryFileHelperTrait;

    /**
     * all possible types of assets
     *
     * @internal
     *
     * @var array
     */
    public static $types = ['folder', 'image', 'text', 'audio', 'video', 'document', 'archive', 'unknown'];

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
    protected $type;

    /**
     * @internal
     *
     * @var string
     */
    protected $filename;

    /**
     * @internal
     *
     * @var string
     */
    protected $path;

    /**
     * @internal
     *
     * @var string
     */
    protected $mimetype;

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
     * @var resource|null
     */
    protected $stream;

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
     * @var array
     */
    protected $properties = null;

    /**
     * @internal
     *
     * @var array|null
     */
    protected $versions = null;

    /**
     * @internal
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * @internal
     *
     * enum('self','propagate') nullable
     *
     * @var string|null
     */
    protected $locked;

    /**
     * List of some custom settings  [key] => value
     * Here there can be stored some data, eg. the video thumbnail files, ...  of the asset, ...
     *
     * @internal
     *
     * @var array
     */
    protected $customSettings = [];

    /**
     * @internal
     *
     * @var bool
     */
    protected $hasMetaData = false;

    /**
     * @internal
     *
     * @var array|null
     */
    protected $siblings;

    /**
     * @internal
     *
     * @var bool|null
     */
    protected $hasSiblings;

    /**
     * @internal
     *
     * @var array|null
     */
    protected $scheduledTasks = null;

    /**
     * @internal
     *
     * @var bool
     */
    protected $_dataChanged = false;

    /**
     * @internal
     *
     * @var int
     */
    protected $versionCount;

    /**
     *
     * @return array
     */
    public static function getTypes()
    {
        return self::$types;
    }

    /**
     * Static helper to get an asset by the passed path
     *
     * @param string $path
     * @param bool $force
     *
     * @return static|null
     */
    public static function getByPath($path, $force = false)
    {
        $path = Element\Service::correctPath($path);

        try {
            $asset = new Asset();
            $asset->getDao()->getByPath($path);

            return static::getById($asset->getId(), $force);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @internal
     *
     * @param Asset $asset
     *
     * @return bool
     */
    protected static function typeMatch(Asset $asset)
    {
        $staticType = get_called_class();
        if ($staticType != Asset::class) {
            if (!$asset instanceof $staticType) {
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
            $asset = \Pimcore\Cache\Runtime::get($cacheKey);
            if ($asset && static::typeMatch($asset)) {
                return $asset;
            }
        }

        if ($force || !($asset = \Pimcore\Cache::load($cacheKey))) {
            $asset = new Asset();
            try {
                $asset->getDao()->getById($id);
                $className = 'Pimcore\\Model\\Asset\\' . ucfirst($asset->getType());

                /** @var Asset $asset */
                $asset = self::getModelFactory()->build($className);
                \Pimcore\Cache\Runtime::set($cacheKey, $asset);
                $asset->getDao()->getById($id);
                $asset->__setDataVersionTimestamp($asset->getModificationDate());

                $asset->resetDirtyMap();

                \Pimcore\Cache::save($asset, $cacheKey);
            } catch (NotFoundException $e) {
                return null;
            }
        } else {
            \Pimcore\Cache\Runtime::set($cacheKey, $asset);
        }

        if (!$asset || !static::typeMatch($asset)) {
            return null;
        }

        return $asset;
    }

    /**
     * @param int $parentId
     * @param array $data
     * @param bool $save
     *
     * @return Asset
     */
    public static function create($parentId, $data = [], $save = true)
    {

        // create already the real class for the asset type, this is especially for images, because a system-thumbnail
        // (tree) is generated immediately after creating an image
        $class = Asset::class;
        if (array_key_exists('filename', $data) && (array_key_exists('data', $data) || array_key_exists('sourcePath', $data) || array_key_exists('stream', $data))) {
            if (array_key_exists('data', $data) || array_key_exists('stream', $data)) {
                $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/asset-create-tmp-file-' . uniqid() . '.' . File::getFileExtension($data['filename']);
                if (array_key_exists('data', $data)) {
                    File::put($tmpFile, $data['data']);
                    $mimeType = MimeTypes::getDefault()->guessMimeType($tmpFile);
                    unlink($tmpFile);
                } else {
                    $streamMeta = stream_get_meta_data($data['stream']);
                    if (file_exists($streamMeta['uri'])) {
                        // stream is a local file, so we don't have to write a tmp file
                        $mimeType = MimeTypes::getDefault()->guessMimeType($streamMeta['uri']);
                    } else {
                        // write a tmp file because the stream isn't a pointer to the local filesystem
                        $isRewindable = @rewind($data['stream']);
                        $dest = fopen($tmpFile, 'w+', false, File::getContext());
                        stream_copy_to_stream($data['stream'], $dest);
                        $mimeType = MimeTypes::getDefault()->guessMimeType($tmpFile);

                        if (!$isRewindable) {
                            $data['stream'] = $dest;
                        } else {
                            fclose($dest);
                            unlink($tmpFile);
                        }
                    }
                }
            } else {
                $mimeType = MimeTypes::getDefault()->guessMimeType($data['sourcePath']);
                if (is_file($data['sourcePath'])) {
                    $data['stream'] = fopen($data['sourcePath'], 'rb', false, File::getContext());
                }

                unset($data['sourcePath']);
            }

            $type = self::getTypeFromMimeMapping($mimeType, $data['filename']);
            $class = '\\Pimcore\\Model\\Asset\\' . ucfirst($type);
            if (array_key_exists('type', $data)) {
                unset($data['type']);
            }
        }

        /** @var Asset $asset */
        $asset = self::getModelFactory()->build($class);
        $asset->setParentId($parentId);
        self::checkCreateData($data);
        $asset->setValues($data);

        if ($save) {
            $asset->save();
        }

        return $asset;
    }

    /**
     * @param array $config
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getList($config = [])
    {
        if (!\is_array($config)) {
            throw new \Exception('Unable to initiate list class - please provide valid configuration array');
        }

        $listClass = Listing::class;

        $list = self::getModelFactory()->build($listClass);
        $list->setValues($config);

        return $list;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param array $config
     *
     * @return int total count
     */
    public static function getTotalCount($config = [])
    {
        $list = static::getList($config);
        $count = $list->getTotalCount();

        return $count;
    }

    /**
     * @internal
     *
     * @param string $mimeType
     * @param string $filename
     *
     * @return string
     */
    public static function getTypeFromMimeMapping($mimeType, $filename)
    {
        if ($mimeType == 'directory') {
            return 'folder';
        }

        $type = null;

        $mappings = [
            'unknown' => ["/\.stp$/"],
            'image' => ['/image/', "/\.eps$/", "/\.ai$/", "/\.svgz$/", "/\.pcx$/", "/\.iff$/", "/\.pct$/", "/\.wmf$/"],
            'text' => ['/text/', '/xml$/'],
            'audio' => ['/audio/'],
            'video' => ['/video/'],
            'document' => ['/msword/', '/pdf/', '/powerpoint/', '/office/', '/excel/', '/opendocument/'],
            'archive' => ['/zip/', '/tar/'],
        ];

        foreach ($mappings as $assetType => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $mimeType . ' .' . File::getFileExtension($filename))) {
                    $type = $assetType;
                    break;
                }
            }

            // break at first match
            if ($type) {
                break;
            }
        }

        if (!$type) {
            $type = 'unknown';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        // additional parameters (e.g. "versionNote" for the version note)
        $params = [];
        if (func_num_args() && is_array(func_get_arg(0))) {
            $params = func_get_arg(0);
        }

        $isUpdate = false;
        $differentOldPath = null;

        try {
            $preEvent = new AssetEvent($this, $params);

            if ($this->getId()) {
                $isUpdate = true;
                \Pimcore::getEventDispatcher()->dispatch($preEvent, AssetEvents::PRE_UPDATE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch($preEvent, AssetEvents::PRE_ADD);
            }

            $params = $preEvent->getArguments();

            $this->correctPath();

            // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
            // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
            // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                $this->beginTransaction();

                try {
                    if (!$isUpdate) {
                        $this->getDao()->create();
                    }

                    // get the old path from the database before the update is done
                    $oldPath = null;
                    if ($isUpdate) {
                        $oldPath = $this->getDao()->getCurrentFullPath();
                    }

                    $this->update($params);

                    $storage = Storage::get('asset');
                    // if the old path is different from the new path, update all children
                    $updatedChildren = [];
                    if ($oldPath && $oldPath != $this->getRealFullPath()) {
                        try {
                            $storage->move($oldPath, $this->getRealFullPath());
                            $differentOldPath = $oldPath;
                            $this->getDao()->updateWorkspaces();
                            $updatedChildren = $this->getDao()->updateChildPaths($oldPath);
                        } catch (UnableToMoveFile $e) {
                            //nothing to do
                        }

                        $this->relocateThumbnails($oldPath);
                    }

                    // lastly create a new version if necessary
                    // this has to be after the registry update and the DB update, otherwise this would cause problem in the
                    // $this->__wakeUp() method which is called by $version->save(); (path correction for version restore)
                    if ($this->getType() != 'folder') {
                        $this->saveVersion(false, false, isset($params['versionNote']) ? $params['versionNote'] : null);
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
                foreach ($updatedChildren as $assetId) {
                    $tag = 'asset_' . $assetId;
                    $additionalTags[] = $tag;

                    // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                    \Pimcore\Cache\Runtime::set($tag, null);
                }
            }
            $this->clearDependentCache($additionalTags);
            $this->setDataChanged(false);

            if ($isUpdate) {
                $updateEvent = new AssetEvent($this);
                if ($differentOldPath) {
                    $updateEvent->setArgument('oldPath', $differentOldPath);
                }
                \Pimcore::getEventDispatcher()->dispatch($updateEvent, AssetEvents::POST_UPDATE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(new AssetEvent($this), AssetEvents::POST_ADD);
            }

            return $this;
        } catch (\Exception $e) {
            $failureEvent = new AssetEvent($this);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                \Pimcore::getEventDispatcher()->dispatch($failureEvent, AssetEvents::POST_UPDATE_FAILURE);
            } else {
                \Pimcore::getEventDispatcher()->dispatch($failureEvent, AssetEvents::POST_ADD_FAILURE);
            }

            throw $e;
        }
    }

    /**
     * @internal
     * @throws \Exception
     */
    public function correctPath()
    {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if (!Element\Service::isValidKey($this->getKey(), 'asset')) {
                throw new \Exception("invalid filename '" . $this->getKey() . "' for asset with id [ " . $this->getId() . ' ]');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            if ($this->getFilename() === '..' || $this->getFilename() === '.') {
                throw new \Exception('Cannot create asset called ".." or "."');
            }

            $parent = Asset::getById($this->getParentId());
            if ($parent) {
                // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
                // that is currently in the parent asset (in memory), because this might have changed but wasn't not saved
                $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath() . '/'));
            } else {
                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath('/');
            }
        } elseif ($this->getId() == 1) {
            // some data in root node should always be the same
            $this->setParentId(0);
            $this->setPath('/');
            $this->setFilename('');
            $this->setType('folder');
        }

        // do not allow PHP and .htaccess files
        if (preg_match("@\.ph(p[\d+]?|t|tml|ps|ar)$@i", $this->getFilename()) || $this->getFilename() == '.htaccess') {
            $this->setFilename($this->getFilename() . '.txt');
        }

        if (mb_strlen($this->getFilename()) > 255) {
            throw new \Exception('Filenames longer than 255 characters are not allowed');
        }

        if (Asset\Service::pathExists($this->getRealFullPath())) {
            $duplicate = Asset::getByPath($this->getRealFullPath());
            if ($duplicate instanceof Asset && $duplicate->getId() != $this->getId()) {
                throw new \Exception('Duplicate full path [ ' . $this->getRealFullPath() . ' ] - cannot save asset');
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
        $storage = Storage::get('asset');
        $this->updateModificationInfos();

        // use current file name in order to prevent problems when filename has changed
        // (otherwise binary data would be overwritten with old binary data with rename() in save method)
        $path = $this->getDao()->getCurrentFullPath();
        if (!$path) {
            // this is happen during a restore from the recycle bin
            $path = $this->getRealFullPath();
        }

        $typeChanged = false;

        if ($this->getType() != 'folder') {
            if ($this->getDataChanged()) {
                $src = $this->getStream();
                $sourceUri = stream_get_meta_data($src)['uri'];
                try {
                    $targetUri = stream_get_meta_data($storage->readStream($path));
                } catch (\Exception $e) {
                    $targetUri = null;
                }

                if ($targetUri !== $sourceUri) {
                    if ($storage->fileExists($path)) {
                        // We don't open a stream on existing files, because they could be possibly used by versions
                        // using hardlinks, so it's safer to delete them first, so the inode and therefore also the
                        // versioning information persists. Using the stream on the existing file would overwrite the
                        // contents of the inode and therefore leads to wrong version data
                        $storage->delete($path);
                    }

                    $storage->writeStream($path, $src);
                }

                $this->stream = null; // set stream to null, so that the source stream isn't used anymore after saving

                $mimeType = $storage->mimeType($path);
                $this->setMimetype($mimeType);

                // set type
                $type = self::getTypeFromMimeMapping($mimeType, $this->getFilename());
                if ($type != $this->getType()) {
                    $this->setType($type);
                    $typeChanged = true;
                }

                // not only check if the type is set but also if the implementation can be found
                $className = 'Pimcore\\Model\\Asset\\' . ucfirst($this->getType());
                if (!self::getModelFactory()->supports($className)) {
                    throw new \Exception('unable to resolve asset implementation with type: ' . $this->getType());
                }
            }
        } else {
            $storage->createDirectory($path);
        }

        if (!$this->getType()) {
            $this->setType('unknown');
        }

        $this->postPersistData();

        // save properties
        $this->getProperties();
        $this->getDao()->deleteAllProperties();
        if (is_array($this->getProperties()) && count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('asset');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = new Dependency();
        $d->setSourceType('asset');
        $d->setSourceId($this->getId());

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement['id'] == $this->getId() && $requirement['type'] == 'asset') {
                // dont't add a reference to yourself
                continue;
            } else {
                $d->addRequirement($requirement['id'], $requirement['type']);
            }
        }
        $d->save();

        $this->getDao()->update();

        //set asset to registry
        $cacheKey = self::getCacheKey($this->getId());
        \Pimcore\Cache\Runtime::set($cacheKey, $this);
        if (get_class($this) == 'Asset' || $typeChanged) {
            // get concrete type of asset
            // this is important because at the time of creating an asset it's not clear which type (resp. class) it will have
            // the type (image, document, ...) depends on the mime-type
            \Pimcore\Cache\Runtime::set($cacheKey, null);
            Asset::getById($this->getId()); // call it to load it to the runtime cache again
        }

        $this->closeStream();
    }

    /**
     * @internal
     */
    protected function postPersistData()
    {
        // hook for the save process, can be overwritten in implementations, such as Image
    }

    /**
     * @param bool $setModificationDate
     * @param bool $saveOnlyVersion
     * @param string $versionNote version note
     *
     * @return null|Version
     *
     * @throws \Exception
     */
    public function saveVersion($setModificationDate = true, $saveOnlyVersion = true, $versionNote = null)
    {
        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $event = new AssetEvent($this, [
                    'saveVersionOnly' => true,
                ]);
                \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::PRE_UPDATE);
            }

            // set date
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            // create version
            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the asset)
            $assetsConfig = \Pimcore\Config::getSystemConfiguration('assets');
            if ((is_null($assetsConfig['versions']['days'] ?? null) && is_null($assetsConfig['versions']['steps'] ?? null))
                || (!empty($assetsConfig['versions']['steps']))
                || !empty($assetsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($assetsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $event = new AssetEvent($this, [
                    'saveVersionOnly' => true,
                ]);
                \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::POST_UPDATE);
            }

            return $version;
        } catch (\Exception $e) {
            $event = new AssetEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::POST_UPDATE_FAILURE);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFullPath()
    {
        $path = $this->getPath() . $this->getFilename();

        if (Tool::isFrontend()) {
            return $this->getFrontendFullPath();
        }

        return $path;
    }

    /**
     * Returns the full path of the asset (listener aware)
     *
     * @return string
     *
     * @internal
     */
    public function getFrontendFullPath()
    {
        $path = $this->getPath() . $this->getFilename();
        $path = urlencode_ignore_slash($path);

        $prefix = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['frontend_prefixes']['source'];
        $path = $prefix . $path;

        $event = new GenericEvent($this, [
            'frontendPath' => $path,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_PATH);

        return $event->getArgument('frontendPath');
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
        $path = $this->getRealPath() . $this->getFilename();

        return $path;
    }

    /**
     * @return array
     */
    public function getSiblings()
    {
        if ($this->siblings === null) {
            $list = new Asset\Listing();
            // string conversion because parentId could be 0
            $list->addConditionParam('parentId = ?', (string)$this->getParentId());
            $list->addConditionParam('id != ?', $this->getId());
            $list->setOrderKey('filename');
            $list->setOrder('asc');
            $this->siblings = $list->getAssets();
        }

        return $this->siblings;
    }

    /**
     * @return bool
     */
    public function hasSiblings()
    {
        if (is_bool($this->hasSiblings)) {
            if (($this->hasSiblings && empty($this->siblings)) || (!$this->hasSiblings && !empty($this->siblings))) {
                return $this->getDao()->hasSiblings();
            } else {
                return $this->hasSiblings;
            }
        }

        return $this->getDao()->hasSiblings();
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return false;
    }

    /**
     * @return Asset[]
     */
    public function getChildren()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLocked()
    {
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
     * @throws \League\Flysystem\FilesystemException
     */
    private function deletePhysicalFile()
    {
        $storage = Storage::get('asset');
        if ($this->getType() != 'folder') {
            $storage->delete($this->getRealFullPath());
        } else {
            $storage->deleteDirectory($this->getRealFullPath());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(bool $isNested = false)
    {
        if ($this->getId() == 1) {
            throw new \Exception('root-node cannot be deleted');
        }

        \Pimcore::getEventDispatcher()->dispatch(new AssetEvent($this), AssetEvents::PRE_DELETE);

        $this->beginTransaction();

        try {
            $this->closeStream();

            // remove children
            if ($this->hasChildren()) {
                foreach ($this->getChildren() as $child) {
                    $child->delete(true);
                }
            }

            $versions = $this->getVersions();
            foreach ($versions as $version) {
                $version->delete();
            }

            // remove permissions
            $this->getDao()->deleteAllPermissions();

            // remove all properties
            $this->getDao()->deleteAllProperties();

            // remove all metadata
            $this->getDao()->deleteAllMetadata();

            // remove all tasks
            $this->getDao()->deleteAllTasks();

            // remove dependencies
            $d = $this->getDependencies();
            $d->cleanAllForElement($this);

            // remove from resource
            $this->getDao()->delete();

            $this->commit();

            // remove file on filesystem
            if (!$isNested) {
                $fullPath = $this->getRealFullPath();
                if ($fullPath != '/..' && !strpos($fullPath,
                        '/../') && $this->getKey() !== '.' && $this->getKey() !== '..') {
                    $this->deletePhysicalFile();
                }
            }

            $this->clearThumbnails(true);
        } catch (\Exception $e) {
            $this->rollBack();
            $failureEvent = new AssetEvent($this);
            $failureEvent->setArgument('exception', $e);
            \Pimcore::getEventDispatcher()->dispatch($failureEvent, AssetEvents::POST_DELETE_FAILURE);
            Logger::crit($e);
            throw $e;
        }

        // empty asset cache
        $this->clearDependentCache();

        // clear asset from registry
        \Pimcore\Cache\Runtime::set(self::getCacheKey($this->getId()), null);

        \Pimcore::getEventDispatcher()->dispatch(new AssetEvent($this), AssetEvents::POST_DELETE);
    }

    /**
     * {@inheritdoc}
     */
    public function clearDependentCache($additionalTags = [])
    {
        try {
            $tags = [$this->getCacheTag(), 'asset_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            \Pimcore\Cache::clearTags($tags);
        } catch (\Exception $e) {
            Logger::crit($e);
        }
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
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return (string)$this->filename;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function getModificationDate()
    {
        return (int)$this->modificationDate;
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
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int)$creationDate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->filename = (string)$filename;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        return $this->setFilename($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setModificationDate($modificationDate)
    {
        $this->markFieldDirty('modificationDate');

        $this->modificationDate = (int)$modificationDate;

        return $this;
    }

    /**
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->parentId = (int)$parentId;
        $this->parent = null;

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
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $stream = $this->getStream();
        if ($stream) {
            return stream_get_contents($stream);
        }

        return '';
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $handle = tmpfile();
        fwrite($handle, $data);
        $this->setStream($handle);

        return $this;
    }

    /**
     * @return resource|null
     */
    public function getStream()
    {
        if ($this->stream) {
            if (get_resource_type($this->stream) !== 'stream') {
                $this->stream = null;
            } elseif (!@rewind($this->stream)) {
                $this->stream = null;
            }
        }

        if (!$this->stream && $this->getType() !== 'folder') {
            try {
                $this->stream = Storage::get('asset')->readStream($this->getRealFullPath());
            } catch (\Exception $e) {
                $this->stream = tmpfile();
            }
        }

        return $this->stream;
    }

    /**
     * @param resource|null $stream
     *
     * @return $this
     */
    public function setStream($stream)
    {
        // close existing stream
        if ($stream !== $this->stream) {
            $this->closeStream();
        }

        if (is_resource($stream)) {
            $this->setDataChanged(true);
            $this->stream = $stream;

            $isRewindable = @rewind($this->stream);

            if (!$isRewindable) {
                $tempFile = $this->getTemporaryFile();
                $dest = fopen($tempFile, 'w+', false, File::getContext());
                $this->stream = $dest;
            }
        } elseif (is_null($stream)) {
            $this->stream = null;
        }

        return $this;
    }

    private function closeStream()
    {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * @return bool
     */
    public function getDataChanged()
    {
        return $this->_dataChanged;
    }

    /**
     * @param bool $changed
     *
     * @return $this
     */
    public function setDataChanged($changed = true)
    {
        $this->_dataChanged = $changed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = 'asset_properties_' . $this->getId();
            $properties = \Pimcore\Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getDao()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = ['asset_properties' => 'asset_properties', $elementCacheTag => $elementCacheTag];
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
        $property->setCtype('asset');
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->properties[$name] = $property;

        return $this;
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
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int)$userOwner;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserModification($userModification)
    {
        $this->markFieldDirty('userModification');

        $this->userModification = (int)$userModification;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        if ($this->versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->versions;
    }

    /**
     * @param Version[] $versions
     *
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @internal
     *
     * @param bool $keep whether to delete this file on shutdown or not
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getTemporaryFile(bool $keep = false)
    {
        return self::getTemporaryFileFromStream($this->getStream(), $keep);
    }

    /**
     * @internal
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getLocalFile()
    {
        return self::getLocalFileFromStream($this->getStream());
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomSetting($key, $value)
    {
        $this->customSettings[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getCustomSetting($key)
    {
        if (is_array($this->customSettings) && array_key_exists($key, $this->customSettings)) {
            return $this->customSettings[$key];
        }

        return null;
    }

    /**
     * @param string $key
     */
    public function removeCustomSetting($key)
    {
        if (is_array($this->customSettings) && array_key_exists($key, $this->customSettings)) {
            unset($this->customSettings[$key]);
        }
    }

    /**
     * @return array
     */
    public function getCustomSettings()
    {
        return $this->customSettings;
    }

    /**
     * @param mixed $customSettings
     *
     * @return $this
     */
    public function setCustomSettings($customSettings)
    {
        if (is_string($customSettings)) {
            $customSettings = \Pimcore\Tool\Serialize::unserialize($customSettings);
        }

        if ($customSettings instanceof \stdClass) {
            $customSettings = (array)$customSettings;
        }

        if (!is_array($customSettings)) {
            $customSettings = [];
        }

        $this->customSettings = $customSettings;

        return $this;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * @param string $mimetype
     *
     * @return $this
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    /**
     * @param array $metadata for each array item: mandatory keys: name, type - optional keys: data, language
     *
     * @return self
     *
     * @internal
     *
     */
    public function setMetadataRaw($metadata)
    {
        $this->metadata = $metadata;
        if ($this->metadata) {
            $this->setHasMetaData(true);
        }

        return $this;
    }

    /**
     * @param array|\stdClass[] $metadata for each array item: mandatory keys: name, type - optional keys: data, language
     *
     * @return self
     */
    public function setMetadata($metadata)
    {
        $this->metadata = [];
        $this->setHasMetaData(false);
        if (!empty($metadata)) {
            foreach ((array)$metadata as $metaItem) {
                $metaItem = (array)$metaItem; // also allow object with appropriate keys
                $this->addMetadata($metaItem['name'], $metaItem['type'], $metaItem['data'] ?? null, $metaItem['language'] ?? null);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getHasMetaData()
    {
        return $this->hasMetaData;
    }

    /**
     * @param bool $hasMetaData
     *
     * @return self
     */
    public function setHasMetaData($hasMetaData)
    {
        $this->hasMetaData = (bool)$hasMetaData;

        return $this;
    }

    /**
     * @param string $name
     * @param string $type can be "asset", "checkbox", "date", "document", "input", "object", "select" or "textarea"
     * @param mixed $data
     * @param string|null $language
     *
     * @return self
     */
    public function addMetadata($name, $type, $data = null, $language = null)
    {
        if ($name && $type) {
            $tmp = [];
            $name = str_replace('~', '---', $name);
            if (!is_array($this->metadata)) {
                $this->metadata = [];
            }

            foreach ($this->metadata as $item) {
                if ($item['name'] != $name || $language != $item['language']) {
                    $tmp[] = $item;
                }
            }

            $item = [
                'name' => $name,
                'type' => $type,
                'data' => $data,
                'language' => $language,
            ];

            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

            try {
                /** @var Data $instance */
                $instance = $loader->build($item['type']);
                $transformedData = $instance->transformSetterData($data, $item);
                $item['data'] = $transformedData;
            } catch (UnsupportedException $e) {
            }

            $tmp[] = $item;
            $this->metadata = $tmp;

            $this->setHasMetaData(true);
        }

        return $this;
    }

    /**
     * @param string|null $name
     * @param string|null $language
     * @param bool $strictMatch
     * @param bool $raw
     *
     * @return array|string|null
     */
    public function getMetadata($name = null, $language = null, $strictMatch = false, $raw = false)
    {
        $preEvent = new AssetEvent($this);
        $preEvent->setArgument('metadata', $this->metadata);
        \Pimcore::getEventDispatcher()->dispatch($preEvent, AssetEvents::PRE_GET_METADATA);
        $this->metadata = $preEvent->getArgument('metadata');

        $convert = function ($metaData) {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            $transformedData = $metaData['data'];

            try {
                /** @var Data $instance */
                $instance = $loader->build($metaData['type']);
                $transformedData = $instance->transformGetterData($metaData['data'], $metaData);
            } catch (UnsupportedException $e) {
            }

            return $transformedData;
        };

        if ($name) {
            if ($language === null) {
                $language = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
            }

            $data = null;
            foreach ($this->metadata as $md) {
                if ($md['name'] == $name) {
                    if ($language == $md['language']) {
                        if ($raw) {
                            return $md;
                        }

                        return $convert($md);
                    }
                    if (empty($md['language']) && !$strictMatch) {
                        if ($raw) {
                            return $md;
                        }
                        $data = $md;
                    }
                }
            }

            if ($data) {
                if ($raw) {
                    return $data;
                }

                return $convert($data);
            }

            return null;
        }

        $metaData = $this->getObjectVar('metadata');
        $result = [];
        if (is_array($metaData)) {
            foreach ($metaData as $md) {
                $md = (array)$md;
                if (!$raw) {
                    $md['data'] = $convert($md);
                }
                $result[] = $md;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledTasks()
    {
        if ($this->scheduledTasks === null) {
            $taskList = new Schedule\Task\Listing();
            $taskList->setCondition("cid = ? AND ctype='asset'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }

        return $this->scheduledTasks;
    }

    /**
     * @param array $scheduledTasks
     *
     * @return $this
     */
    public function setScheduledTasks($scheduledTasks)
    {
        $this->scheduledTasks = $scheduledTasks;

        return $this;
    }

    private function saveScheduledTasks()
    {
        $this->getScheduledTasks();
        $this->getDao()->deleteAllTasks();

        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setDao(null);
                $task->setCid($this->getId());
                $task->setCtype('asset');
                $task->save();
            }
        }
    }

    /**
     * @param bool $formatted
     * @param int $precision
     *
     * @return string|int
     */
    public function getFileSize($formatted = false, $precision = 2)
    {
        try {
            $bytes = Storage::get('asset')->fileSize($this->getRealFullPath());
        } catch (\Exception $e) {
            $bytes = 0;
        }

        if ($formatted) {
            return formatBytes($bytes, $precision);
        }

        return $bytes;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if ($this->parent === null) {
            $this->setParent(Asset::getById($this->getParentId()));
        }

        return $this->parent;
    }

    /**
     * @param Asset $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        if ($parent instanceof Asset) {
            $this->parentId = $parent->getId();
        }

        return $this;
    }

    public function __sleep()
    {
        $parentVars = parent::__sleep();
        $blockedVars = ['scheduledTasks', 'hasChildren', 'versions', 'parent', 'stream'];

        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the asset (eg. for a new version), including children for recyclebin
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the asset
            $blockedVars = array_merge($blockedVars, ['children', 'properties']);
        }

        return array_diff($parentVars, $blockedVars);
    }

    public function __wakeup()
    {
        if ($this->isInDumpState()) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Asset::getById($this->getId());
            if ($originalElement) {
                $this->setFilename($originalElement->getFilename());
                $this->setPath($originalElement->getRealPath());
            }
        }

        if ($this->isInDumpState() && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        $this->setInDumpState(false);
    }

    public function __destruct()
    {
        // close open streams
        $this->closeStream();
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
        $this->versionCount = (int)$versionCount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [parent::resolveDependencies()];

        if ($this->hasMetaData) {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

            foreach ($this->getMetadata() as $metaData) {
                if (!empty($metaData['data'])) {
                    /** @var ElementInterface $elementData */
                    $elementData = $metaData['data'];
                    $elementType = $metaData['type'];

                    try {
                        /** @var DataDefinitionInterface $implementation */
                        $implementation = $loader->build($elementType);
                        $dependencies[] = $implementation->resolveDependencies($elementData, $metaData);
                    } catch (UnsupportedException $e) {
                    }
                }
            }
        }

        return array_merge(...$dependencies);
    }

    public function __clone()
    {
        parent::__clone();
        $this->parent = null;
        $this->versions = null;
        $this->hasSiblings = null;
        $this->siblings = null;
        $this->scheduledTasks = null;
        $this->closeStream();
    }

    /**
     * @param bool $force
     */
    public function clearThumbnails($force = false)
    {
        if ($this->getDataChanged() || $force) {
            foreach (['thumbnail', 'asset_cache'] as $storageName) {
                $storage = Storage::get($storageName);
                $contents = $storage->listContents($this->getRealPath());

                /** @var StorageAttributes $item */
                foreach ($contents as $item) {
                    if (preg_match('@(image|video|pdf)\-thumb__' . $this->getId() . '__@', $item->path())) {
                        if ($item->isDir()) {
                            $storage->deleteDirectory($item->path());
                        } elseif ($item->isFile()) {
                            $storage->delete($item->path());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $oldPath
     *
     * @throws \League\Flysystem\FilesystemException
     */
    private function relocateThumbnails(string $oldPath)
    {
        $oldParent = dirname($oldPath);
        $newParent = dirname($this->getRealFullPath());
        $storage = Storage::get('thumbnail');

        try {
            $contents = $storage->listContents($oldParent);
            /** @var StorageAttributes $item */
            foreach ($contents as $item) {
                if (preg_match('@(image|video|pdf)\-thumb__' . $this->getId() . '__@', $item->path())) {
                    $replacePath = ltrim($newParent, '/') .'/' . basename($item->path());
                    if (!$storage->fileExists($replacePath)) {
                        $storage->move($item->path(), $replacePath);
                    }
                }
            }

            //required in case if there is only renaming on parent
            $storage->move($oldPath, $this->getRealFullPath());
        } catch (UnableToMoveFile $e) {
            // noting to do
        }
    }

    /**
     * @param string $name
     */
    public function clearThumbnail($name)
    {
        try {
            Storage::get('thumbnail')->deleteDirectory($this->getRealPath() . 'image-thumb__' . $this->getId() . '__' . $name);
        } catch (\Exception $e) {
            // noting to do
        }
    }
}
