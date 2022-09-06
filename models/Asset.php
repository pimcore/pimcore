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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model;

use Doctrine\DBAL\Exception\DeadlockException;
use Exception;
use function is_array;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToMoveFile;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model\Asset\Dao;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Listing;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\DataDefinitionInterface;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\Traits\ScheduledTasksTrait;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;
use stdClass;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Mime\MimeTypes;

/**
 * @method Dao getDao()
 * @method bool __isBasedOnLatestData()
 * @method int getChildAmount($user = null)
 * @method string|null getCurrentFullPath()
 */
class Asset extends Element\AbstractElement
{
    use ScheduledTasksTrait;
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
     * @var string
     */
    protected $type = '';

    /**
     * @internal
     *
     * @var string|null
     */
    protected $filename;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $mimetype;

    /**
     * @internal
     *
     * @var resource|null
     */
    protected $stream;

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
     * @var bool
     */
    protected $dataChanged = false;

    /**
     * {@inheritdoc}
     */
    protected function getBlockedVars(): array
    {
        $blockedVars = ['scheduledTasks', 'hasChildren', 'versions', 'parent', 'stream'];

        if (!$this->isInDumpState()) {
            // for caching asset
            $blockedVars = array_merge($blockedVars, ['children', 'properties']);
        }

        return $blockedVars;
    }

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
     * @param array|bool $force
     *
     * @return static|null
     */
    public static function getByPath($path, $force = false)
    {
        if (!$path) {
            return null;
        }

        $path = Element\Service::correctPath($path);

        try {
            $asset = new static();
            $asset->getDao()->getByPath($path);

            return static::getById($asset->getId(), Service::prepareGetByIdParams($force, __METHOD__, func_num_args() > 1));
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
        $staticType = static::class;
        if ($staticType !== Asset::class) {
            if (!$asset instanceof $staticType) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $id
     * @param array|bool $force
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

        $params = Service::prepareGetByIdParams($force, __METHOD__, func_num_args() > 1);

        if (!$params['force'] && RuntimeCache::isRegistered($cacheKey)) {
            $asset = RuntimeCache::get($cacheKey);
            if ($asset && static::typeMatch($asset)) {
                return $asset;
            }
        }

        if ($params['force'] || !($asset = Cache::load($cacheKey))) {
            $asset = new static();

            try {
                $asset->getDao()->getById($id);
                $className = 'Pimcore\\Model\\Asset\\' . ucfirst($asset->getType());

                if (get_class($asset) !== $className) {
                    /** @var Asset $asset */
                    $asset = self::getModelFactory()->build($className);
                    $asset->getDao()->getById($id);
                }

                RuntimeCache::set($cacheKey, $asset);
                $asset->__setDataVersionTimestamp($asset->getModificationDate());

                $asset->resetDirtyMap();

                Cache::save($asset, $cacheKey);
            } catch (NotFoundException $e) {
                return null;
            }
        } else {
            RuntimeCache::set($cacheKey, $asset);
        }

        if (!$asset || !static::typeMatch($asset)) {
            return null;
        }

        \Pimcore::getEventDispatcher()->dispatch(
            new AssetEvent($asset, ['params' => $params]),
            AssetEvents::POST_LOAD
        );

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
                    self::checkMaxPixels($tmpFile, $data);
                    $mimeType = MimeTypes::getDefault()->guessMimeType($tmpFile);
                    unlink($tmpFile);
                } else {
                    $streamMeta = stream_get_meta_data($data['stream']);
                    if (file_exists($streamMeta['uri'])) {
                        // stream is a local file, so we don't have to write a tmp file
                        self::checkMaxPixels($streamMeta['uri'], $data);
                        $mimeType = MimeTypes::getDefault()->guessMimeType($streamMeta['uri']);
                    } else {
                        // write a tmp file because the stream isn't a pointer to the local filesystem
                        $isRewindable = @rewind($data['stream']);
                        $dest = fopen($tmpFile, 'w+', false, File::getContext());
                        stream_copy_to_stream($data['stream'], $dest);
                        self::checkMaxPixels($tmpFile, $data);
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
                if (is_dir($data['sourcePath'])) {
                    $mimeType = 'directory';
                } else {
                    self::checkMaxPixels($data['sourcePath'], $data);
                    $mimeType = MimeTypes::getDefault()->guessMimeType($data['sourcePath']);
                    if (is_file($data['sourcePath'])) {
                        $data['stream'] = fopen($data['sourcePath'], 'rb', false, File::getContext());
                    }
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

    private static function checkMaxPixels(string $localPath, array $data): void
    {
        // this check is intentionally done in Asset::create() because in Asset::update() it would result
        // in an additional download from remote storage if configured, so in terms of performance
        // this is the more efficient way
        $maxPixels = (int) Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['max_pixels'];
        if ($size = @getimagesize($localPath)) {
            $imagePixels = (int) ($size[0] * $size[1]);
            if ($imagePixels > $maxPixels) {
                Logger::error("Image to be created {$localPath} (temp. path) exceeds max pixel size of {$maxPixels}, you can change the value in config pimcore.assets.image.max_pixels");

                $diff = sqrt(1 + ($maxPixels / $imagePixels));
                $suggestion_0 = (int) round($size[0] / $diff, -2, PHP_ROUND_HALF_DOWN);
                $suggestion_1 = (int) round($size[1] / $diff, -2, PHP_ROUND_HALF_DOWN);

                $mp = $maxPixels / 1_000_000;

                throw new ValidationException("<p>Image dimensions of <em>{$data['filename']}</em> are too large.</p>
<p>Max size: <code>{$mp}</code> <abbr title='Million pixels'>Megapixels</abbr></p>
<p>Suggestion: resize to <code>{$suggestion_0}&times;{$suggestion_1}</code> pixels or smaller.</p>");
            }
        }
    }

    /**
     * @param array $config
     *
     * @return Listing
     *
     * @throws Exception
     */
    public static function getList($config = [])
    {
        if (!is_array($config)) {
            throw new Exception('Unable to initiate list class - please provide valid configuration array');
        }

        $listClass = Listing::class;

        /** @var Listing $list */
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
            'image' => ['/image/', "/\.eps$/", "/\.ai$/", "/\.svgz$/", "/\.pcx$/", "/\.iff$/", "/\.pct$/", "/\.wmf$/", '/photoshop/'],
            'text' => ['/text/', '/xml$/', '/\.json$/'],
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
                $this->dispatchEvent($preEvent, AssetEvents::PRE_UPDATE);
            } else {
                $this->dispatchEvent($preEvent, AssetEvents::PRE_ADD);
            }

            $params = $preEvent->getArguments();

            $this->correctPath();

            $params['isUpdate'] = $isUpdate; // we need that in $this->update() for certain types (image, video, document)

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
                        $differentOldPath = $oldPath;

                        try {
                            $storage->move($oldPath, $this->getRealFullPath());
                        } catch (UnableToMoveFile $e) {
                            //update children, if unable to move parent
                            $this->updateChildPaths($storage, $oldPath);
                        }

                        $this->getDao()->updateWorkspaces();

                        $updatedChildren = $this->getDao()->updateChildPaths($oldPath);
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
                } catch (Exception $e) {
                    try {
                        $this->rollBack();
                    } catch (Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::error((string) $er);
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
                    RuntimeCache::set($tag, null);
                }
            }
            $this->clearDependentCache($additionalTags);

            if ($this->getDataChanged()) {
                if (in_array($this->getType(), ['image', 'video', 'document'])) {
                    $this->addToUpdateTaskQueue();
                }
            }

            $this->setDataChanged(false);

            $postEvent = new AssetEvent($this, $params);
            if ($isUpdate) {
                if ($differentOldPath) {
                    $postEvent->setArgument('oldPath', $differentOldPath);
                }
                $this->dispatchEvent($postEvent, AssetEvents::POST_UPDATE);
            } else {
                $this->dispatchEvent($postEvent, AssetEvents::POST_ADD);
            }

            return $this;
        } catch (Exception $e) {
            $failureEvent = new AssetEvent($this, $params);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                $this->dispatchEvent($failureEvent, AssetEvents::POST_UPDATE_FAILURE);
            } else {
                $this->dispatchEvent($failureEvent, AssetEvents::POST_ADD_FAILURE);
            }

            throw $e;
        }
    }

    /**
     * @internal
     *
     * @throws Exception|DuplicateFullPathException
     */
    public function correctPath()
    {
        // set path
        if ($this->getId() != 1) { // not for the root node
            if (!Element\Service::isValidKey($this->getKey(), 'asset')) {
                throw new Exception("invalid filename '" . $this->getKey() . "' for asset with id [ " . $this->getId() . ' ]');
            }

            if (!$this->getParentId()) {
                throw new Exception('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new Exception("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
            }

            if ($this->getFilename() === '..' || $this->getFilename() === '.') {
                throw new Exception('Cannot create asset called ".." or "."');
            }

            $parent = Asset::getById($this->getParentId());
            if (!$parent) {
                throw new Exception('ParentID not found.');
            }

            // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
            // that is currently in the parent asset (in memory), because this might have changed but wasn't not saved
            $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath() . '/'));
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
            throw new Exception('Filenames longer than 255 characters are not allowed');
        }

        if (Asset\Service::pathExists($this->getRealFullPath())) {
            $duplicate = Asset::getByPath($this->getRealFullPath());
            if ($duplicate instanceof Asset && $duplicate->getId() != $this->getId()) {
                $duplicateFullPathException = new DuplicateFullPathException('Duplicate full path [ ' . $this->getRealFullPath() . ' ] - cannot save asset');
                $duplicateFullPathException->setDuplicateElement($duplicate);

                throw $duplicateFullPathException;
            }
        }

        $this->validatePathLength();
    }

    /**
     * @internal
     *
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws Exception
     */
    protected function update($params = [])
    {
        $storage = Storage::get('asset');
        $this->updateModificationInfos();

        $path = $this->getRealFullPath();
        $typeChanged = false;

        if ($this->getType() != 'folder') {
            if ($this->getDataChanged()) {
                $src = $this->getStream();

                if (!$storage->fileExists($path) || !stream_is_local($storage->readStream($path))) {
                    // write stream directly if target file doesn't exist or if target is a remote storage
                    // this is because we don't have hardlinks there, so we don't need to consider them (see below)
                    $storage->writeStream($path, $src);
                } else {
                    // We don't open a stream on existing files, because they could be possibly used by versions
                    // using hardlinks, so it's safer to write them to a temp file first, so the inode and therefore
                    // also the versioning information persists. Using the stream on the existing file would overwrite the
                    // contents of the inode and therefore leads to wrong version data
                    $pathInfo = pathinfo($this->getFilename());
                    $tempFilePath = $this->getRealPath() . uniqid('temp_');
                    if ($pathInfo['extension'] ?? false) {
                        $tempFilePath .= '.' . $pathInfo['extension'];
                    }

                    $storage->writeStream($tempFilePath, $src);
                    $storage->delete($path);
                    $storage->move($tempFilePath, $path);
                }

                // delete old legacy file if exists
                $dbPath = $this->getDao()->getCurrentFullPath();
                if ($dbPath !== $path && $storage->fileExists($dbPath)) {
                    $storage->delete($dbPath);
                }

                $this->closeStream(); // set stream to null, so that the source stream isn't used anymore after saving

                $mimeType = $storage->mimeType($path);
                $this->setMimeType($mimeType);

                // set type
                $type = self::getTypeFromMimeMapping($mimeType, $this->getFilename());
                if ($type != $this->getType()) {
                    $this->setType($type);
                    $typeChanged = true;
                }

                // not only check if the type is set but also if the implementation can be found
                $className = 'Pimcore\\Model\\Asset\\' . ucfirst($this->getType());
                if (!self::getModelFactory()->supports($className)) {
                    throw new Exception('unable to resolve asset implementation with type: ' . $this->getType());
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
        RuntimeCache::set($cacheKey, $this);
        if (static::class === Asset::class || $typeChanged) {
            // get concrete type of asset
            // this is important because at the time of creating an asset it's not clear which type (resp. class) it will have
            // the type (image, document, ...) depends on the mime-type
            RuntimeCache::set($cacheKey, null);
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
     * @throws Exception
     */
    public function saveVersion($setModificationDate = true, $saveOnlyVersion = true, $versionNote = null)
    {
        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $event = new AssetEvent($this, [
                    'saveVersionOnly' => true,
                ]);
                $this->dispatchEvent($event, AssetEvents::PRE_UPDATE);
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
            $assetsConfig = Config::getSystemConfiguration('assets');
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
                $this->dispatchEvent($event, AssetEvents::POST_UPDATE);
            }

            return $version;
        } catch (Exception $e) {
            $event = new AssetEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
            ]);
            $this->dispatchEvent($event, AssetEvents::POST_UPDATE_FAILURE);

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

        $prefix = Pimcore::getContainer()->getParameter('pimcore.config')['assets']['frontend_prefixes']['source'];
        $path = $prefix . $path;

        $event = new GenericEvent($this, [
            'frontendPath' => $path,
        ]);

        $this->dispatchEvent($event, FrontendEvents::ASSET_PATH);

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
            if ($this->getParentId()) {
                $list = new Asset\Listing();
                $list->addConditionParam('parentId = ?', $this->getParentId());
                if ($this->getId()) {
                    $list->addConditionParam('id != ?', $this->getId());
                }
                $list->setOrderKey('filename');
                $list->setOrder('asc');
                $this->siblings = $list->getAssets();
            } else {
                $this->siblings = [];
            }
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
     * @throws FilesystemException
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
            throw new Exception('root-node cannot be deleted');
        }

        $this->dispatchEvent(new AssetEvent($this), AssetEvents::PRE_DELETE);

        $this->beginTransaction();

        try {
            $this->closeStream();

            // remove children
            if ($this->hasChildren()) {
                foreach ($this->getChildren() as $child) {
                    $child->delete(true);
                }
            }

            // Dispatch Symfony Message Bus to delete versions
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new VersionDeleteMessage(Service::getElementType($this), $this->getId())
            );

            // remove all properties
            $this->getDao()->deleteAllProperties();

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

            //remove target parent folder preview thumbnails
            $this->clearFolderThumbnails($this);
        } catch (Exception $e) {
            try {
                $this->rollBack();
            } catch (Exception $er) {
                // PDO adapter throws exceptions if rollback fails
                Logger::info((string) $er);
            }

            $failureEvent = new AssetEvent($this);
            $failureEvent->setArgument('exception', $e);
            $this->dispatchEvent($failureEvent, AssetEvents::POST_DELETE_FAILURE);
            Logger::crit((string) $e);

            throw $e;
        }

        // empty asset cache
        $this->clearDependentCache();

        // clear asset from registry
        RuntimeCache::set(self::getCacheKey($this->getId()), null);

        $this->dispatchEvent(new AssetEvent($this), AssetEvents::POST_DELETE);
    }

    /**
     * {@inheritdoc}
     */
    public function clearDependentCache($additionalTags = [])
    {
        try {
            $tags = [$this->getCacheTag(), 'asset_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
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
    public function getType()
    {
        return $this->type;
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
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (string)$type;

        return $this;
    }

    /**
     * @return string|false
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
            } catch (Exception $e) {
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
                $tempFile = $this->getLocalFileFromStream($this->stream);
                $dest = fopen($tempFile, 'rb', false, File::getContext());
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
        return $this->dataChanged;
    }

    /**
     * @param bool $changed
     *
     * @return $this
     */
    public function setDataChanged($changed = true)
    {
        $this->dataChanged = $changed;

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
     * @throws Exception
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
     * @throws Exception
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
            $customSettings = Serialize::unserialize($customSettings);
        }

        if ($customSettings instanceof stdClass) {
            $customSettings = (array)$customSettings;
        }

        if (!is_array($customSettings)) {
            $customSettings = [];
        }

        $this->customSettings = $customSettings;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * @param string $mimetype
     *
     * @return $this
     */
    public function setMimeType($mimetype)
    {
        $this->mimetype = (string)$mimetype;

        return $this;
    }

    /**
     * @param array $metadata for each array item: mandatory keys: name, type - optional keys: data, language
     *
     * @return $this
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
     * @param array[]|stdClass[] $metadata for each array item: mandatory keys: name, type - optional keys: data, language
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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

            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

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
     * @param string $name
     * @param string|null $language
     *
     * @return $this
     */
    public function removeMetadata(string $name, ?string $language = null)
    {
        if ($name) {
            $tmp = [];
            $name = str_replace('~', '---', $name);
            if (!is_array($this->metadata)) {
                $this->metadata = [];
            }

            foreach ($this->metadata as $item) {
                if ($item['name'] === $name && ($language == $item['language'] || $language === '*')) {
                    continue;
                }
                $tmp[] = $item;
            }

            $this->metadata = $tmp;
            $this->setHasMetaData(!empty($this->metadata));
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
        $this->dispatchEvent($preEvent, AssetEvents::PRE_GET_METADATA);
        $this->metadata = $preEvent->getArgument('metadata');

        $convert = function ($metaData) {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
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
                $language = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
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
     * @param bool $formatted
     * @param int $precision
     *
     * @return string|int
     */
    public function getFileSize($formatted = false, $precision = 2)
    {
        try {
            $bytes = Storage::get('asset')->fileSize($this->getRealFullPath());
        } catch (Exception $e) {
            $bytes = 0;
        }

        if ($formatted) {
            return formatBytes($bytes, $precision);
        }

        return $bytes;
    }

    /**
     * @return Asset|null
     */
    public function getParent() /** : ?Asset */
    {
        $parent = parent::getParent();

        return $parent instanceof Asset ? $parent : null;
    }

    /**
     * @param Asset|null $parent
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

    public function __wakeup()
    {
        if ($this->isInDumpState()) {
            // set current parent and path, this is necessary because the serialized data can have a different path than the original element (element was moved)
            $originalElement = Asset::getById($this->getId());
            if ($originalElement) {
                $this->setParentId($originalElement->getParentId());
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
    protected function resolveDependencies(): array
    {
        $dependencies = [parent::resolveDependencies()];

        if ($this->hasMetaData) {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

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
                $storage->deleteDirectory($this->getRealPath() . $this->getId());
            }

            $this->getDao()->deleteFromThumbnailCache();
        }
    }

    /**
     * @param FilesystemOperator $storage
     * @param string $oldPath
     * @param string|null $newPath
     *
     * @throws FilesystemException
     */
    private function updateChildPaths(FilesystemOperator $storage, string $oldPath, string $newPath = null)
    {
        if ($newPath === null) {
            $newPath = $this->getRealFullPath();
        }

        try {
            $children = $storage->listContents($oldPath, true);
            foreach ($children as $child) {
                if ($child['type'] === 'file') {
                    $src  = $child['path'];
                    $dest = str_replace($oldPath, $newPath, '/' . $src);
                    $storage->move($src, $dest);
                }
            }

            $storage->deleteDirectory($oldPath);
        } catch (UnableToMoveFile $e) {
            // noting to do
        }
    }

    /**
     * @param string $oldPath
     *
     * @throws FilesystemException
     */
    private function relocateThumbnails(string $oldPath)
    {
        if ($this instanceof Folder) {
            $oldThumbnailsPath = $oldPath;
            $newThumbnailsPath = $this->getRealFullPath();
        } else {
            $oldThumbnailsPath = dirname($oldPath) . '/' . $this->getId();
            $newThumbnailsPath = $this->getRealPath() . $this->getId();
        }

        if ($oldThumbnailsPath === $newThumbnailsPath) {
            //path is equal, probably file name changed - so clear all thumbnails
            $this->clearThumbnails(true);
        } else {
            //remove source parent folder preview thumbnails
            $sourceFolder = Asset::getByPath(dirname($oldPath));
            if ($sourceFolder) {
                $this->clearFolderThumbnails($sourceFolder);
            }

            //remove target parent folder preview thumbnails
            $this->clearFolderThumbnails($this);

            foreach (['thumbnail', 'asset_cache'] as $storageName) {
                $storage = Storage::get($storageName);

                try {
                    $storage->move($oldThumbnailsPath, $newThumbnailsPath);
                } catch (UnableToMoveFile $e) {
                    //update children, if unable to move parent
                    $this->updateChildPaths($storage, $oldPath);
                }
            }
        }
    }

    /**
     * @param Asset $asset
     */
    private function clearFolderThumbnails(Asset $asset): void
    {
        do {
            if ($asset instanceof Folder) {
                $asset->clearThumbnails(true);
            }

            $asset = $asset->getParent();
        } while ($asset !== null);
    }

    /**
     * @param string $name
     */
    public function clearThumbnail($name)
    {
        try {
            Storage::get('thumbnail')->deleteDirectory($this->getRealPath().'/'.$this->getId().'/image-thumb__'.$this->getId().'__'.$name);
            $this->getDao()->deleteFromThumbnailCache($name);
        } catch (Exception $e) {
            // noting to do
        }
    }

    /**
     * @internal
     */
    protected function addToUpdateTaskQueue(): void
    {
        Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
            new AssetUpdateTasksMessage($this->getId())
        );
    }

    /**
     * @return string
     */
    public function getFrontendPath(): string
    {
        $path = $this->getFullPath();
        if (!\preg_match('@^(https?|data):@', $path)) {
            $path = \Pimcore\Tool::getHostUrl() . $path;
        }

        return $path;
    }
}
