<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model;

use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use Normalizer;
use Pimcore;
use Pimcore\Asset\StorageQueue\FrontendPathResolver;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Event\Model\Asset\ResolveMimeTypeEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\File;
use Pimcore\Helper\MimeTypeHelper;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model\Asset\Dao;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ThumbnailConfig;
use Pimcore\Model\Asset\Listing;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\DataDefinitionInterface;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\Traits\ScheduledTasksTrait;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Exception\DataStateChangedException;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Exception\SaveAbortedExceptionInterface;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tool;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;
use stdClass;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Throwable;
use TypeError;

/**
 * @method Dao getDao()
 * @method bool __isBasedOnLatestData()
 * @method int getChildAmount($user = null)
 * @method string|null getCurrentFullPath()
 * @method Version|null getLatestVersion(?int $userId = null, bool $includingPublished = false)
 */
class Asset extends Element\AbstractElement
{
    use ScheduledTasksTrait;
    use TemporaryFileHelperTrait;

    public const CUSTOM_SETTING_PROCESSING_FAILED = 'pimcore-asset-processing-failed';

    /**
     * custom settings of the embedded meta data (see MetaData\EmbeddedMetaDataTrait), which belong to the binary data
     */
    private const EMBEDDED_META_DATA_CUSTOM_SETTINGS = ['embeddedMetaData', 'embeddedMetaDataExtracted'];

    /**
     * identifies the current data: a new value is assigned whenever the data is replaced or restored (see
     * getDataGeneration())
     */
    private const CUSTOM_SETTING_DATA_GENERATION = 'pimcore-asset-data-generation';

    /**
     * set while the processing of the data by the asset update tasks queue is pending (see isProcessingPending())
     */
    private const CUSTOM_SETTING_PROCESSING_PENDING = 'pimcore-asset-processing-pending';

    /**
     * identifies the results of the last processing of the data: a new value is assigned whenever a processing
     * finishes (see setProcessingPending()), so that the state of the data (see getDataState()) changes even if
     * already processed data was processed again on demand
     */
    private const CUSTOM_SETTING_PROCESSING_REVISION = 'pimcore-asset-processing-revision';

    /**
     * custom settings describing the state of the data (see getDataState()), which belong to the data like the
     * derived settings (see getDataDerivedCustomSettingKeys())
     */
    private const DATA_STATE_CUSTOM_SETTINGS = [
        self::CUSTOM_SETTING_DATA_GENERATION,
        self::CUSTOM_SETTING_PROCESSING_PENDING,
        self::CUSTOM_SETTING_PROCESSING_REVISION,
        'checksum',
    ];

    /**
     * types whose data is processed by the asset update tasks queue (see \Pimcore\Messenger\Handler\AssetUpdateTasksHandler)
     */
    private const PROCESSED_TYPES = ['image', 'video', 'document'];

    /**
     * @internal
     *
     */
    protected string $type = '';

    /**
     * @internal
     *
     */
    protected ?string $filename = null;

    /**
     * @internal
     *
     */
    protected ?string $mimetype = null;

    /**
     * @internal
     *
     * @var resource|null
     */
    protected $stream;

    private bool $streamIsPlaceholder = false;

    /**
     * @internal
     *
     */
    protected ?array $versions = null;

    /**
     * @internal
     *
     */
    protected array $metadata = [];

    /**
     * List of some custom settings  [key] => value
     * Here there can be stored some data, eg. the video thumbnail files, ...  of the asset, ...
     *
     * @internal
     *
     */
    protected array $customSettings = [];

    /**
     * @internal
     *
     * @var bool whether custom settings should go into the cache or not -> depending on the size of the data stored there
     */
    protected bool $customSettingsCanBeCached = true;

    /**
     * @internal
     */
    protected bool $customSettingsNeedRefresh = false;

    /**
     * @internal
     *
     */
    protected bool $hasMetaData = false;

    /**
     * @internal
     *
     */
    protected ?Listing $siblings = null;

    /**
     * @internal
     *
     */
    protected bool $dataChanged = false;

    /**
     * whether the changed data was restored (see restoreStream()) instead of being replaced by other data
     *
     * @internal
     */
    protected bool $dataRestored = false;

    /**
     * whether the custom settings didn't (completely) belong to the data when the asset was dumped (e.g. for a version
     * or the recycle bin): they were not loaded yet, so that the dump doesn't contain them, or the data had been
     * replaced without saving the asset, so that the settings derived from the previous data were not invalidated
     * yet. null if unknown (dumps created before this was tracked, and assets that were not loaded from a dump).
     *
     * @internal
     */
    protected ?bool $customSettingsIncomplete = null;

    /**
     * the data (see getDataGeneration()) this instance finished the processing of (see setProcessingPending()): its
     * results are saved as the current derived settings, in contrast to the outdated derived settings of an instance
     * loaded before the data or its processing was changed by others, which are replaced by the stored ones when the
     * instance is saved (see update())
     */
    private ?string $finishedProcessingGeneration = null;

    /**
     * the state of the data (see getDataState()) the results of a processing being saved were generated for (see
     * saveProcessingResults())
     */
    private ?string $expectedDataState = null;

    /**
     * @internal
     */
    protected ?int $dataModificationDate = null;

    public function getDataModificationDate(): ?int
    {
        return $this->dataModificationDate;
    }

    /**
     * @return $this
     */
    public function setDataModificationDate(?int $dataModificationDate): static
    {
        $this->dataModificationDate = $dataModificationDate;

        return $this;
    }

    protected function getBlockedVars(): array
    {
        $blockedVars = [
            'scheduledTasks',
            'versions',
            'stream',
            'streamIsPlaceholder',
            'finishedProcessingGeneration',
            'expectedDataState',
        ];

        if (!$this->isInDumpState()) {
            // for caching asset
            $blockedVars = array_merge($blockedVars, ['children', 'properties']);

            if ($this->customSettingsCanBeCached === false) {
                $blockedVars[] = 'customSettings';
            }
        }

        return $blockedVars;
    }

    public function __sleep(): array
    {
        // a dump (e.g. version, recycle bin) has to know whether the dumped custom settings belong to the dumped data,
        // which is not the case if they were not loaded yet (see refreshCustomSettings()) or if the data was replaced
        // without saving the asset (which invalidates the settings derived from the previous data, see update())
        $this->customSettingsIncomplete = $this->isInDumpState()
            ? ($this->customSettingsNeedRefresh || $this->isDataReplaced())
            : null;

        $blockedVars = parent::__sleep();
        if (in_array('customSettings', $blockedVars)) {
            $this->customSettingsNeedRefresh = true;
        }

        return $blockedVars;
    }

    public static function getTypes(): array
    {
        $assetsConfig = Config::getSystemConfiguration('assets');
        if (isset($assetsConfig['type_definitions']['map']) && is_array($assetsConfig['type_definitions']['map'])) {
            return array_keys($assetsConfig['type_definitions']['map']);
        }

        return [];
    }

    /**
     * Static helper to get an asset by the passed path
     *
     *
     */
    public static function getByPath(string $path, array $params = []): static|null
    {
        if (!$path) {
            return null;
        }

        $path = Element\Service::correctPath($path);

        try {
            $asset = new static();

            Element\Service::getByPathWithNfcFallback(
                fn (string $candidate) => $asset->getDao()->getByPath($candidate),
                $path
            );

            return static::getById(
                $asset->getId(),
                Service::prepareGetByIdParams($params)
            );
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @internal
     *
     *
     */
    protected static function typeMatch(Asset $asset): bool
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
     * @param array{force?: bool, ...} $params
     */
    public static function getById(int $id, array $params = []): ?static
    {
        if ($id < 1) {
            return null;
        }

        $cacheKey = self::getCacheKey($id);

        $params = Service::prepareGetByIdParams($params);

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

                $className = Pimcore::getContainer()->get('pimcore.class.resolver.asset')->resolve($asset->getType());
                /** @var Asset $newAsset */
                $newAsset = self::getModelFactory()->build($className);

                if (get_class($asset) !== get_class($newAsset)) {
                    $asset = $newAsset;
                    $asset->getDao()->getById($id);
                }

                RuntimeCache::set($cacheKey, $asset);
                if ($asset->getModificationDate() !== null) {
                    $asset->__setDataVersionTimestamp($asset->getModificationDate());
                }

                $asset->resetDirtyMap();

                Cache::save($asset, $cacheKey);
            } catch (NotFoundException|UnsupportedException $e) {
                $asset = null;
            }
        } else {
            RuntimeCache::set($cacheKey, $asset);
        }

        if ($asset && static::typeMatch($asset)) {
            Pimcore::getEventDispatcher()->dispatch(
                new AssetEvent($asset, ['params' => $params]),
                AssetEvents::POST_LOAD
            );
        } else {
            $asset = null;
        }

        return $asset;
    }

    public static function create(int $parentId, array $data = [], bool $save = true): Asset
    {
        // create already the real class for the asset type, this is especially for images, because a system-thumbnail
        // (tree) is generated immediately after creating an image
        $type = 'unknown';
        $tmpFile = null;
        if (
            array_key_exists('filename', $data) &&
            (
                array_key_exists('data', $data) ||
                array_key_exists('sourcePath', $data) ||
                array_key_exists('stream', $data)
            )
        ) {
            $mimeTypeHelper = new MimeTypeHelper();
            $mimeType = 'directory';
            $mimeTypeGuessData = null;
            if (array_key_exists('data', $data) || array_key_exists('stream', $data)) {
                $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/asset-create-tmp-file-' . uniqid() . '.' . pathinfo($data['filename'], PATHINFO_EXTENSION);
                $mimeTypeGuessData = $tmpFile;

                if (!str_starts_with($tmpFile, PIMCORE_SYSTEM_TEMP_DIRECTORY)) {
                    throw new InvalidArgumentException('Invalid filename');
                }

                if (array_key_exists('data', $data)) {
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile($tmpFile, $data['data']);
                } else {
                    // guess mime type from stream directly
                    $mimeTypeGuessData = $data['stream'];
                }

                $mimeType = $mimeTypeHelper->guessMimeType(
                    $mimeTypeGuessData
                );
            } else {
                if (!is_dir($data['sourcePath'])) {
                    $mimeTypeGuessData = $data['sourcePath'];
                    if (is_file($data['sourcePath'])) {
                        $data['stream'] = fopen($data['sourcePath'], 'rb', false, File::getContext());
                    }
                    $mimeType = $mimeTypeHelper->guessMimeType($mimeTypeGuessData);
                }
                unset($data['sourcePath']);
            }

            $mimeType ??= 'application/octet-stream';
            $mimeType = self::resolveMimeTypeFromMapping($mimeType, $data['filename']);
            $mimeTypeEvent = new ResolveMimeTypeEvent($data['filename'], $mimeType);
            Pimcore::getEventDispatcher()->dispatch($mimeTypeEvent, AssetEvents::RESOLVE_MIME_TYPE);
            $mimeType = $mimeTypeEvent->getMimeType();

            $type = self::getTypeFromMimeMapping($mimeType, $data['filename']);
            // only check maxpixels if it is an image
            if ($type === 'image' && $mimeTypeGuessData) {
                self::checkMaxPixels($mimeTypeGuessData, $data);
            }

            if (array_key_exists('type', $data)) {
                unset($data['type']);
            }
        } elseif (array_key_exists('type', $data)) {
            $type = $data['type'];
            unset($data['type']);
        }

        $className = Pimcore::getContainer()->get('pimcore.class.resolver.asset')->resolve($type)
            ?? throw new InvalidArgumentException('Invalid asset type provided');

        /** @var Asset $asset */
        $asset = self::getModelFactory()->build($className);
        $asset->setParentId($parentId);
        self::checkCreateData($data);
        $asset->setValues($data);

        if ($save) {
            $asset->save();
        }

        if ($tmpFile !== null && file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        return $asset;
    }

    private static function getImageSizeFromStream(mixed $stream): array
    {
        if (!is_resource($stream)) {
            return [];
        }
        $size = getimagesizefromstring(
            @stream_get_contents($stream)
        );

        return $size === false ? [] : $size;
    }

    private static function checkMaxPixels(mixed $localPathOrStream, array $data): void
    {
        // this check is intentionally done in Asset::create() because in Asset::update() it would result
        // in an additional download from remote storage if configured, so in terms of performance
        // this is the more efficient way
        $maxPixels = (int)Config::getSystemConfiguration('assets')['image']['max_pixels'];
        if (is_string($localPathOrStream)) {
            $size = @getimagesize($localPathOrStream);
        } else {
            $size = self::getImageSizeFromStream($localPathOrStream);
        }
        if ($maxPixels && $size) {
            $imagePixels = ($size[0] * $size[1]);
            if ($imagePixels > $maxPixels) {
                Logger::error("Image to be created {$localPathOrStream} (temp. path) exceeds max pixel size of {$maxPixels}, you can change the value in config pimcore.assets.image.max_pixels");

                $diff = sqrt(1 + $imagePixels / $maxPixels);
                $suggestion_0 = (int)round($size[0] / $diff, -2, PHP_ROUND_HALF_DOWN);
                $suggestion_1 = (int)round($size[1] / $diff, -2, PHP_ROUND_HALF_DOWN);

                $mp = $maxPixels / 1_000_000;

                throw new ValidationException("<p>Image dimensions of <em>{$data['filename']}</em> are too large.</p>
<p>Max size: <code>{$mp}</code> <abbr title='Million pixels'>Megapixels</abbr></p>
<p>Suggestion: resize to <code>{$suggestion_0}&times;{$suggestion_1}</code> pixels or smaller.</p>");
            }
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function getList(array $config = []): Listing
    {
        $listClass = Listing::class;

        /** @var Listing $list */
        $list = self::getModelFactory()->build($listClass);
        $list->setValues($config);

        return $list;
    }

    /**
     *
     *
     * @internal
     */
    public static function resolveMimeTypeFromMapping(string $detectedMimeType, string $filename): string
    {
        if ($detectedMimeType === 'directory') {
            return $detectedMimeType;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            return $detectedMimeType;
        }

        $mappings = Config::getSystemConfiguration('assets')['mime_mappings'] ?? [];
        if (isset($mappings[$extension])) {
            return (string)$mappings[$extension];
        }

        return $detectedMimeType;
    }

    /**
     *
     *
     * @internal
     */
    public static function getTypeFromMimeMapping(string $mimeType, string $filename): string
    {
        if ($mimeType == 'directory') {
            return 'folder';
        }

        $type = null;
        $assetTypes = Config::getSystemConfiguration('assets')['type_definitions']['map'];

        foreach ($assetTypes as $assetType => $assetTypeConfiguration) {
            foreach ($assetTypeConfiguration['matching'] as $pattern) {
                if (preg_match($pattern, $mimeType . ' .' . pathinfo($filename, PATHINFO_EXTENSION))) {
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

    public function save(array $parameters = []): static
    {
        $isUpdate = false;
        $differentOldPath = null;
        $updatedChildren = [];

        $this->retryableFunction(
            beforeRetryables: function () use (&$parameters, &$isUpdate) {
                $preEvent = new AssetEvent($this, $parameters);

                if ($this->getId()) {
                    $isUpdate = true;
                    $this->dispatchEvent($preEvent, AssetEvents::PRE_UPDATE);
                } else {
                    $this->dispatchEvent($preEvent, AssetEvents::PRE_ADD);
                }

                $parameters = $preEvent->getArguments();

                $this->correctPath();

                // need for $this->update() for certain types (image, video, document)
                $parameters['isUpdate'] = $isUpdate;
            },
            retryableFunc: function () use (&$parameters, &$isUpdate, &$differentOldPath, &$updatedChildren) {
                if (!$isUpdate) {
                    $this->getDao()->create();
                }

                // get the old path from the database before the update is done
                $oldPath = null;
                if ($isUpdate) {
                    $oldPath = $this->getDao()->getCurrentFullPath();
                }

                $this->update($parameters);

                $storage = Storage::get('asset');
                // if the old path is different from the new path, update all children
                $updatedChildren = [];
                if ($oldPath && $oldPath != $this->getRealFullPath()) {
                    $differentOldPath = $oldPath;

                    // First make DB updates:
                    $this->getDao()->updateWorkspaces();
                    $updatedChildren = $this->getDao()->updateChildPaths($oldPath);

                    // then update thumbnails
                    // TODO: determine if failure on moving thumbnails should be ignored
                    $this->relocateThumbnails($oldPath);

                    // finally move the actual assets themselves
                    // We do this last so that any prior errors don't require a rollback
                    // on potentially a remote service.
                    $this->moveDirectoryOnStorage($storage, $oldPath);
                }

                // lastly create a new version if necessary
                // this has to be after the registry update and the DB update, otherwise this would cause problem in the
                // $this->__wakeUp() method which is called by $version->save(); (path correction for version restore)
                if ($this->getType() != 'folder') {
                    $this->saveVersion(false, false, $parameters['versionNote'] ?? null);
                    $this->closeStream(); // set stream to null, so that the source stream isn't used anymore after saving
                }
            },
            onCommit: function () use (&$parameters, &$isUpdate, &$differentOldPath, &$updatedChildren) {

                $additionalTags = [];

                foreach ($updatedChildren as $assetId) {
                    $tag = 'asset_' . $assetId;
                    $additionalTags[] = $tag;

                    // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                    RuntimeCache::set($tag, null);
                }

                $this->clearDependentCache($additionalTags);

                if ($differentOldPath) {
                    $this->renewInheritedProperties();
                }

                // add to queue that saves dependencies
                $this->addToDependenciesQueue();

                // replaced data has to be processed. Restored data (see restoreStream()) doesn't, as the data derived
                // from it was restored as well (and processing it again could even discard the restored data), unless
                // the restored state was dumped while its processing was still pending, so the derived data is missing.
                // The previews of restored data are generated again in any case, as the previews of the previous data
                // were cleared when it was restored (see e.g. Image::update()).
                if (in_array($this->getType(), self::PROCESSED_TYPES, true)) {
                    if ($this->isDataReplaced() || ($this->dataRestored && $this->isProcessingPending())) {
                        $this->addUpdateTaskForCurrentData(false);
                    } elseif ($this->dataRestored) {
                        $this->addUpdateTaskForCurrentData(true);
                    }
                }

                $this->setDataChanged(false);
                $this->dataRestored = false;
                $this->customSettingsIncomplete = null;

                $postEvent = new AssetEvent($this, $parameters);
                if ($isUpdate) {
                    if ($differentOldPath) {
                        $postEvent->setArgument('oldPath', $differentOldPath);
                    }
                    $this->dispatchEvent($postEvent, AssetEvents::POST_UPDATE);
                } else {
                    $this->dispatchEvent($postEvent, AssetEvents::POST_ADD);
                }
            },
            onFailure: function ($e) use (&$parameters, &$isUpdate) {
                if ($e instanceof SaveAbortedExceptionInterface) {
                    // not a failure: the save was aborted on purpose (e.g. the results of a processing are discarded,
                    // see saveProcessingResults())
                    return;
                }

                // TODO: we should rollback any files that were moved here,
                // assuming a prior revert has not been done.
                $failureEvent = new AssetEvent($this, $parameters);
                $failureEvent->setArgument('exception', $e);
                if ($isUpdate) {
                    $this->dispatchEvent($failureEvent, AssetEvents::POST_UPDATE_FAILURE);
                } else {
                    $this->dispatchEvent($failureEvent, AssetEvents::POST_ADD_FAILURE);
                }
            }
        );

        return $this;
    }

    /**
     * @internal
     *
     * @throws Exception|DuplicateFullPathException
     */
    public function correctPath(): void
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

            // use the parent's path from the database here (getCurrentFullPath),
            //to ensure the path really exists and does not rely on the path
            // that is currently in the parent asset (in memory),
            //because this might have changed but wasn't not saved
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
                $duplicateFullPathException->setCauseElement($this);

                throw $duplicateFullPathException;
            }
        }

        $this->validatePathLength();
    }

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws Exception
     *
     * @internal
     */
    protected function update(array $params = []): void
    {
        $storage = Storage::get('asset');
        $this->updateModificationInfos();

        // the results of a processing are only saved for the data they were generated for (see
        // saveProcessingResults()), which is checked here, as the asset is locked against concurrent saves from now
        // on (see updateModificationInfos()) until the end of the transaction
        if ($this->expectedDataState !== null) {
            $this->applyProcessingResultsToStoredCustomSettings($this->expectedDataState);
        }

        $path = $this->getRealFullPath();
        $typeChanged = false;

        if ($this->getType() != 'folder') {
            if ($this->getDataChanged()) {
                $src = $this->getStream();

                $existingStream = $storage->fileExists($path) ? $storage->readStream($path) : null;
                if (!$existingStream || !stream_is_local($existingStream)) {
                    // write stream directly if target file doesn't exist or if target is a remote storage
                    // this is because we don't have hardlinks there, so we don't need to consider them (see below)
                    if (is_resource($existingStream)) {
                        fclose($existingStream);
                    }
                    $storage->writeStream($path, $src);
                } else {
                    // We don't open a stream on existing files, because they could be possibly used by versions
                    // using hardlinks, so it's safer to write them to a temp file first, so the inode and therefore
                    // also the versioning information persists. Using the stream on the existing file would overwrite the
                    // contents of the inode and therefore leads to wrong version data
                    fclose($existingStream);
                    $pathInfo = pathinfo($this->getFilename());
                    $tempFilePath = $this->getRealPath() . uniqid('temp_');
                    if ($pathInfo['extension'] ?? false) {
                        $tempFilePath .= '.' . $pathInfo['extension'];
                    }

                    $storage->writeStream($tempFilePath, $src);
                    $storage->delete($path);
                    $storage->move($tempFilePath, $path);
                }

                // the new data gets a new generation (see getDataGeneration()), which tells it apart from the previous
                // data, even if it is identical
                $this->setCustomSetting(self::CUSTOM_SETTING_DATA_GENERATION, bin2hex(random_bytes(8)));

                //generate & save checksum in custom settings. The checksum of the previous data is removed first, so
                // that it doesn't survive if the checksum can't be generated
                $this->removeCustomSetting('checksum');
                $this->generateChecksum();

                // delete old legacy file if exists
                $dbPath = $this->getDao()->getCurrentFullPath();
                if ($dbPath && $dbPath !== $path && $storage->fileExists($dbPath)) {
                    $storage->delete($dbPath);
                }

                if (!is_resource($src)) {
                    $src = $this->getStream();
                }

                $mimeType = null;

                try {
                    $mimeType = $storage->mimeType($path);
                } catch (FilesystemException $e) {
                    // ignore, fallback
                }

                if (!$mimeType || $mimeType === 'application/octet-stream') {
                    $mimeType = (new MimeTypeHelper())->guessMimeType($src) ?? 'application/octet-stream';
                }

                $mimeType = self::resolveMimeTypeFromMapping($mimeType, $this->getFilename());
                $mimeTypeEvent = new ResolveMimeTypeEvent($this->getFilename(), $mimeType, $this, !($params['isUpdate'] ?? false));
                $this->dispatchEvent($mimeTypeEvent, AssetEvents::RESOLVE_MIME_TYPE);
                $mimeType = $mimeTypeEvent->getMimeType();

                $this->setMimeType($mimeType);
                $this->closeStream(); // set stream to null, so that the source stream isn't used anymore after saving

                // set type
                $type = self::getTypeFromMimeMapping($mimeType, $this->getFilename());
                if ($type != $this->getType()) {
                    $this->setType($type);
                    $typeChanged = true;
                }

                // replaced data is processed by the asset update tasks queue after saving (see save()), if the type
                // is processed at all. This is remembered in the custom settings, so that it is part of a dump (e.g.
                // version, recycle bin) created in the meantime, whose derived data is therefore missing (see
                // restoreStream()). Data of a type that isn't processed clears a pending processing of the previous
                // data, as nothing would finish it otherwise.
                if ($this->isDataReplaced()) {
                    $this->setProcessingPending(in_array($type, self::PROCESSED_TYPES, true));
                    // a failed processing of the previous data doesn't concern the replaced data (it would prevent it
                    // from being added to the queue on demand, see addToUpdateTaskQueue())
                    $this->removeCustomSetting(self::CUSTOM_SETTING_PROCESSING_FAILED);
                }

                // not only check if the type is set but also if the implementation can be found
                $className = Pimcore::getContainer()->get('pimcore.class.resolver.asset')->resolve($type);

                if (!self::getModelFactory()->supports($className)) {
                    throw new Exception('unable to resolve asset implementation with type: ' . $this->getType());
                }
            } elseif (($params['isUpdate'] ?? false) && $this->expectedDataState === null) {
                $typeChanged = $this->keepStoredDataSettings();
            }
        } else {
            $storage->createDirectory($path);
        }

        if (!$this->getType()) {
            $this->setType('unknown');
        }

        $this->postPersistData();

        if ($this->isFieldDirty('properties')) {
            // save properties
            $properties = $this->getProperties();
            $this->getDao()->deleteAllProperties();
            foreach ($properties as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('asset');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        $this->getDao()->update();

        // set asset to registry
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
    protected function postPersistData(): void
    {
        // hook for the save process, can be overwritten in implementations, such as Image
    }

    /**
     * Accepts an additional optional argument `array $parameters = []` (read via func_get_arg())
     * with custom arguments that are passed on to the versioning events. It will become a regular
     * method parameter in the next major version.
     *
     * @param string|null $versionNote version note
     *
     * @throws Exception
     */
    public function saveVersion(bool $setModificationDate = true, bool $saveOnlyVersion = true, ?string $versionNote = null /* , array $parameters = [] */): ?Version
    {
        // TODO: promote $parameters to a regular signature parameter in the next major version (2027.1)
        $parameters = 4 <= func_num_args() ? func_get_arg(3) : [];
        if (!is_array($parameters)) {
            throw new TypeError(sprintf('%s(): Argument #4 ($parameters) must be of type array, %s given', __METHOD__, get_debug_type($parameters)));
        }
        $coreParameters = ['saveVersionOnly' => true];
        $eventParameters = array_merge($parameters, $coreParameters);

        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $event = new AssetEvent($this, $eventParameters);
                $this->dispatchEvent($event, AssetEvents::PRE_UPDATE);
                $eventParameters = $event->getArguments();
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
            $assetsConfig = SystemSettingsConfig::get()['assets'];
            if ((is_null($assetsConfig['versions']['days'] ?? null) && is_null($assetsConfig['versions']['steps'] ?? null))
                || (!empty($assetsConfig['versions']['steps']))
                || !empty($assetsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($assetsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $event = new AssetEvent($this, array_merge($eventParameters, $coreParameters));
                $this->dispatchEvent($event, AssetEvents::POST_UPDATE);
            }

            return $version;
        } catch (Exception $e) {
            $event = new AssetEvent($this, array_merge($eventParameters, $coreParameters, ['exception' => $e]));
            $this->dispatchEvent($event, AssetEvents::POST_UPDATE_FAILURE);

            throw $e;
        }
    }

    public function getFullPath(): string
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
     *
     * @internal
     */
    public function getFrontendFullPath(): string
    {
        $path = $this->getPath() . $this->getFilename();

        $prefix = Config::getSystemConfiguration('assets')['frontend_prefixes']['source'];
        if ($prefix !== '' && $prefix !== null) {
            // prefix-based URLs point straight at the storage (CDN/bucket); while a queued
            // folder move is pending the bytes still live under the pre-move prefix
            $path = Pimcore::getContainer()->get(FrontendPathResolver::class)->resolvePhysicalPath($path, $this->getModificationDate());
        }

        $path = urlencode_ignore_slash($path);
        $path = $prefix . $path;

        $event = new GenericEvent($this, [
            'frontendPath' => $path,
        ]);

        $this->dispatchEvent($event, FrontendEvents::ASSET_PATH);

        return $event->getArgument('frontendPath');
    }

    public function getRealPath(): ?string
    {
        return $this->path;
    }

    public function getRealFullPath(): string
    {
        $path = $this->getRealPath() . $this->getFilename();

        return $path;
    }

    public function getSiblings(): Listing
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
                $this->siblings = $list;
            } else {
                $list = new Asset\Listing();
                $list->setAssets([]);
                $this->siblings = $list;
            }
        }

        return $this->siblings;
    }

    public function hasSiblings(): bool
    {
        return $this->getDao()->hasSiblings();
    }

    public function hasChildren(): bool
    {
        return false;
    }

    public function getChildren(): Listing
    {
        return (new Listing())->setAssets([]);
    }

    /**
     * @throws FilesystemException
     */
    private function deletePhysicalFile(): void
    {
        $storage = Storage::get('asset');
        if ($this->getType() != 'folder') {
            $storage->delete($this->getRealFullPath());
        } else {
            $storage->deleteDirectory($this->getRealFullPath());
        }
    }

    public function delete(bool $isNested = false): void
    {
        if ($this->getId() == 1) {
            throw new Exception('root-node cannot be deleted');
        }

        $this->retryableFunction(
            beforeRetryables: function () {
                $this->dispatchEvent(new AssetEvent($this), AssetEvents::PRE_DELETE);
            },
            retryableFunc: function () {
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

            },
            onCommit: function () use ($isNested) {
                // remove file on filesystem
                if (!$isNested) {
                    $fullPath = $this->getRealFullPath();
                    if ($fullPath != '/..' && !strpos($fullPath,
                        '/../') && $this->getKey() !== '.' && $this->getKey() !== '..') {
                        $this->deletePhysicalFile();
                    }

                    //remove target parent folder preview thumbnails
                    $this->clearFolderThumbnails($this);
                }
                $this->clearThumbnails(true);

                // empty asset cache
                $this->clearDependentCache();

                // clear asset from registry
                RuntimeCache::set(self::getCacheKey($this->getId()), null);

                $this->dispatchEvent(new AssetEvent($this), AssetEvents::POST_DELETE);
            },
            onFailure: function ($e) {
                $failureEvent = new AssetEvent($this);
                $failureEvent->setArgument('exception', $e);
                $this->dispatchEvent($failureEvent, AssetEvents::POST_DELETE_FAILURE);
            }
        );
    }

    public function clearDependentCache(array $additionalTags = []): void
    {
        try {
            $tags = [$this->getCacheTag(), 'asset_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getKey(): ?string
    {
        return $this->getFilename();
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function setKey(string $key): static
    {
        return $this->setFilename($key);
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|false
     */
    public function getData(): bool|string
    {
        $stream = $this->getStream();
        if ($stream) {
            return stream_get_contents($stream);
        }

        return '';
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): static
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
                $this->streamIsPlaceholder = false;
            } catch (Exception $e) {
                Logger::error('Unable to read the data of asset ' . $this->getRealFullPath() . ' from storage, returning an empty placeholder stream instead: ' . $e);
                $this->stream = tmpfile();
                $this->streamIsPlaceholder = true;
            }
        }

        return $this->stream;
    }

    /**
     * Returns true if the stream returned by getStream() is an empty placeholder that was substituted
     * because the asset's binary data could not be read from storage (e.g. the file is missing),
     * false if the stream contains the asset's actual data.
     */
    public function isStreamPlaceholder(): bool
    {
        return $this->streamIsPlaceholder;
    }

    public function getChecksum(): string
    {
        if (!$checksum = $this->getCustomSetting('checksum')) {
            $this->generateChecksum();
            $checksum = $this->getCustomSetting('checksum');
        }

        // generateChecksum may fail to set the checksum, in which case we fall back to empty string.
        return $checksum ?? '';
    }

    /**
     * @internal
     */
    public function generateChecksum(): void
    {
        try {
            $this->setCustomSetting('checksum', Storage::get('asset')->checksum($this->getRealFullPath()));
        } catch (UnableToProvideChecksum $e) {
            // There are circumstances in which the adapter is unable to calculate the checksum for a given file.
            // In those cases, we ignore the exception.
            Logger::error((string) $e);

            return;
        }
        $this->getDao()->updateCustomSettings();
    }

    /**
     * @param resource|null $stream
     *
     * @return $this
     */
    public function setStream($stream): static
    {
        // close existing stream
        if ($stream !== $this->stream) {
            $this->closeStream();
        }

        if (is_resource($stream)) {
            $this->setDataChanged();
            $this->dataRestored = false;
            $this->setDataModificationDate(time());
            $this->stream = $stream;
            $this->streamIsPlaceholder = false;

            // embedded meta data (see EmbeddedMetaDataTrait) belongs to the binary data, so it has to be extracted
            // again from the new data. This is done here for all asset types, as the type can change together with
            // the data (e.g. image -> document) and the new type would otherwise keep the meta data of the old one.
            // It has to happen as soon as the data is assigned (and not during save()), so that the meta data can
            // already be extracted from the new data before the asset is saved.
            foreach (self::EMBEDDED_META_DATA_CUSTOM_SETTINGS as $key) {
                $this->removeCustomSetting($key);
            }

            $isRewindable = @rewind($this->stream);

            if (!$isRewindable) {
                $tempFile = $this->getLocalFileFromStream($this->stream);
                $dest = fopen($tempFile, 'rb', false, File::getContext());
                $this->stream = $dest;
            }
        } elseif (is_null($stream)) {
            $this->stream = null;
            $this->streamIsPlaceholder = false;
        }

        return $this;
    }

    /**
     * Returns a new stream of the data, which is independent of the stream of this asset (see getStream()). It can
     * be assigned to another asset (e.g. a copy), which closes its stream when it is saved: closing the stream of
     * this asset instead would make it fall back to the data in the storage and lose data assigned but not saved yet.
     * If the stream of this asset can't be opened again with the same data (e.g. a php://memory stream), its data is
     * copied to a temporary file, which the new stream reads.
     *
     * @return resource|null
     *
     * @throws Exception
     *
     * @internal
     */
    public function getStreamCopy(): mixed
    {
        $stream = $this->getStream();
        if (!is_resource($stream)) {
            return null;
        }

        $streamCopy = fopen(self::getLocalFileFromStream($stream), 'rb', false, File::getContext());
        if (!is_resource($streamCopy)) {
            throw new Exception(sprintf('Unable to open a new stream of the data of asset %s', $this->getRealFullPath()));
        }

        return $streamCopy;
    }

    /**
     * Assigns the data of the given asset (a new stream of it, see getStreamCopy()) together with the custom settings
     * belonging to it, so that this asset becomes a copy of the given one. The derived settings of the source are
     * taken over instead of being generated again (which could fail or differ), so the data is treated like restored
     * data (see restoreStream()): if the processing of the source is pending, the copy is processed as well, otherwise
     * only its previews are generated. If the data of the source was replaced without saving it, its derived settings
     * still belong to its previous data, so the data is treated like replaced data instead (see setStream()) and the
     * copy is processed.
     *
     * @return $this
     *
     * @throws Exception
     *
     * @internal
     */
    public function copyDataFrom(Asset $source): static
    {
        // also makes sure the custom settings of the source are loaded completely
        // (they might not be, if the source came from the cache)
        $this->setCustomSettings($source->getCustomSettings());

        if ($source->isDataReplaced()) {
            $this->setStream($source->getStreamCopy());
        } else {
            $this->customSettingsIncomplete = false;
            $this->restoreStream($source->getStreamCopy());
        }

        return $this;
    }

    /**
     * Assigns binary data that belongs to the current state of the asset, e.g. the data stored by a version or
     * the recycle bin. In contrast to setStream(), the data derived from the binary data (embedded meta data,
     * dimensions, page count, ...) is kept, as it was generated from exactly this data (see isDataReplaced()).
     *
     * @param resource $stream
     *
     * @return $this
     *
     * @internal
     */
    public function restoreStream(mixed $stream): static
    {
        if ($this->customSettingsIncomplete !== false) {
            // the custom settings of the dump this asset was loaded from don't belong to its data (true: they were not
            // loaded when the asset was dumped, or the data had been replaced without saving the asset), or the dump
            // was created before it was tracked whether they do and whether the processing of the data was still
            // pending (null). In both cases the data derived from the restored data is unknown and has to be generated
            // again, so the restored data is treated like replaced data (which is how it was treated in the past)
            $this->setStream($stream);

            return $this;
        }

        $embeddedMetaDataSettings = [];
        foreach (self::EMBEDDED_META_DATA_CUSTOM_SETTINGS as $key) {
            $embeddedMetaDataSettings[$key] = $this->getCustomSetting($key);
        }

        $this->setStream($stream);

        foreach ($embeddedMetaDataSettings as $key => $value) {
            if ($value !== null) {
                $this->setCustomSetting($key, $value);
            }
        }

        $this->dataRestored = true;

        return $this;
    }

    private function closeStream(): void
    {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
            $this->stream = null;
        }
        $this->streamIsPlaceholder = false;
    }

    public function getDataChanged(): bool
    {
        return $this->dataChanged;
    }

    /**
     * Whether the binary data was replaced by other data, so that data derived from it (e.g. dimensions,
     * page count) has to be generated again. This is not the case if the changed data was restored
     * (see restoreStream()), as the derived data belongs to the restored data.
     *
     * @internal
     */
    public function isDataReplaced(): bool
    {
        return $this->dataChanged && !$this->dataRestored;
    }

    /**
     * Whether the processing of the data by the asset update tasks queue, which generates the data derived from it
     * (e.g. dimensions, page count, embedded meta data), is still pending
     *
     * @internal
     */
    public function isProcessingPending(): bool
    {
        return (bool) $this->getCustomSetting(self::CUSTOM_SETTING_PROCESSING_PENDING);
    }

    /**
     * Marks the processing of the current data as pending or finished. Finishing it assigns a new revision to the
     * results (see getDataState()), even if the data had been processed before.
     *
     * @internal
     */
    public function setProcessingPending(bool $pending): void
    {
        if ($pending) {
            $this->setCustomSetting(self::CUSTOM_SETTING_PROCESSING_PENDING, true);
            $this->finishedProcessingGeneration = null;
        } else {
            $this->finishedProcessingGeneration = $this->getDataGeneration() ?? $this->finishedProcessingGeneration;
            $this->removeCustomSetting(self::CUSTOM_SETTING_PROCESSING_PENDING);
            $this->setCustomSetting(self::CUSTOM_SETTING_PROCESSING_REVISION, bin2hex(random_bytes(8)));
        }
    }

    /**
     * Returns the value identifying the current data, which changes whenever the data is replaced or restored (even
     * by identical data), or null for assets whose data wasn't saved since this was introduced. It tells whether the
     * data is still the one a task of the asset update tasks queue was created for (see save()).
     *
     * @internal
     */
    public function getDataGeneration(): ?string
    {
        return self::normalizeDataGeneration($this->getCustomSetting(self::CUSTOM_SETTING_DATA_GENERATION));
    }

    /**
     * Returns the keys of the custom settings derived from the data by the asset update tasks queue (see
     * \Pimcore\Messenger\Handler\AssetUpdateTasksHandler), which are therefore unknown while the processing of the
     * data is pending (see isProcessingPending()) and belong to the data like the settings describing its state
     * (see getDataState())
     *
     * @return string[]
     *
     * @internal
     */
    public static function getDataDerivedCustomSettingKeys(): array
    {
        return array_merge(self::EMBEDDED_META_DATA_CUSTOM_SETTINGS, [self::CUSTOM_SETTING_PROCESSING_FAILED]);
    }

    /**
     * The fields which belong to the data in the storage (its type and mime type, the settings describing its state,
     * see getDataState(), and the settings derived from it, see getDataDerivedCustomSettingKeys()) are authoritative
     * as stored in the database when this instance is saved without changing the data, but the data or its processing
     * was changed by others since this instance was loaded (it was replaced or restored, or its pending processing
     * was finished by the asset update tasks queue): saving the outdated fields of this instance would discard a
     * pending processing (whose task would find nothing left to process) or the results of a finished one, and it
     * would attach the type, the checksum and the derived settings of the previous data to the current one. As the
     * current data can be of another type than this instance (e.g. an image replaced by a document), the derived
     * settings of both types are taken over. Only the results of a processing this instance finished itself are
     * saved. Must be called within the transaction saving the asset, as it locks the asset against concurrent saves
     * until the end of the transaction.
     *
     * @return bool whether the type of the asset changed
     */
    private function keepStoredDataSettings(): bool
    {
        $stored = $this->getDao()->getDataBoundFieldsForUpdate();
        $storedSettings = $stored['customSettings'];

        $storedState = self::buildDataState($storedSettings);
        if ($storedState === $this->getDataState()) {
            return false;
        }

        $storedGeneration = self::normalizeDataGeneration($storedSettings[self::CUSTOM_SETTING_DATA_GENERATION] ?? null);
        if ($storedGeneration !== null && $storedGeneration === $this->finishedProcessingGeneration) {
            return false;
        }

        $typeChanged = false;
        $derivedKeys = static::getDataDerivedCustomSettingKeys();
        if ($stored['type'] !== null && $stored['type'] !== $this->getType()) {
            $storedClass = Pimcore::getContainer()->get('pimcore.class.resolver.asset')->resolve($stored['type']);
            if (is_a($storedClass, self::class, true)) {
                $derivedKeys = array_unique(array_merge($derivedKeys, $storedClass::getDataDerivedCustomSettingKeys()));
            }
            $this->setType($stored['type']);
            $typeChanged = true;
        }
        if ($stored['mimetype'] !== null) {
            $this->setMimeType($stored['mimetype']);
        }

        foreach (array_merge(self::DATA_STATE_CUSTOM_SETTINGS, $derivedKeys) as $key) {
            if (($storedSettings[$key] ?? null) === null) {
                $this->removeCustomSetting($key);
            } else {
                $this->setCustomSetting($key, $storedSettings[$key]);
            }
        }

        return $typeChanged;
    }

    /**
     * Saves the results of processing the data (the settings derived from it, see getDataDerivedCustomSettingKeys(),
     * and the state of its processing, see setProcessingPending()), unless the state of the data changed since the
     * results were generated (see getDataState()): the data was replaced or restored, or its processing finished by
     * others in the meantime, so the results belong to previous data and are discarded, as they would overwrite the
     * state of the current data. This is checked within the transaction saving the asset, while the asset is locked
     * against concurrent saves, so the state can't change in between. The other custom settings are saved as
     * currently stored, as they might have been changed by others while the data was processed.
     *
     * @param string $dataState the state of the data the results were generated for
     *
     * @return bool whether the results were saved
     *
     * @throws Exception
     *
     * @internal
     */
    public function saveProcessingResults(string $dataState, array $parameters = []): bool
    {
        $this->expectedDataState = $dataState;

        try {
            $this->save($parameters);
        } catch (Exception $e) {
            // thrown by update() (see applyProcessingResultsToStoredCustomSettings())
            if ($e instanceof DataStateChangedException) {
                return false;
            }

            throw $e;
        } finally {
            $this->expectedDataState = null;
        }

        return true;
    }

    /**
     * Whether the given state of the data (see getDataState()) is the one currently stored in the database, i.e. the
     * data and its processing weren't changed by others since the state was determined. The stored state is read
     * while the asset is locked shortly, so that a change being saved concurrently is seen.
     *
     * @throws Exception
     *
     * @internal
     */
    public function isDataStateStored(string $dataState): bool
    {
        $db = Db::get();
        $db->beginTransaction();

        try {
            $storedDataState = $this->getStoredDataStateForUpdate();
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();

            throw $e;
        }

        return $storedDataState === $dataState;
    }

    /**
     * Applies the results of a processing (see saveProcessingResults()) to the custom settings as currently stored
     * in the database: the settings which belong to the data are taken from this instance, the others as stored
     *
     * @throws DataStateChangedException if the data state stored in the database isn't the expected one
     */
    private function applyProcessingResultsToStoredCustomSettings(string $expectedDataState): void
    {
        $stored = $this->getDao()->getDataBoundFieldsForUpdate();
        $storedSettings = $stored['customSettings'];

        $storedState = self::buildDataState($storedSettings);
        if ($storedState !== $expectedDataState) {
            throw new DataStateChangedException(sprintf(
                'The data of asset %s was replaced or restored, or its processing finished, in the meantime',
                $this->getRealFullPath()
            ));
        }

        $customSettings = $this->getCustomSettings();
        foreach (array_merge(self::DATA_STATE_CUSTOM_SETTINGS, static::getDataDerivedCustomSettingKeys()) as $key) {
            if (array_key_exists($key, $customSettings)) {
                $storedSettings[$key] = $customSettings[$key];
            } else {
                unset($storedSettings[$key]);
            }
        }

        $this->setCustomSettings($storedSettings);
    }

    /**
     * Returns a value identifying the state of the data of this instance, which changes whenever the data is replaced
     * or restored (see getDataGeneration()) and whenever its processing starts or finishes (see
     * setProcessingPending()), even if already processed data is processed again. Comparing it with the stored state
     * (see getStoredDataStateForUpdate()) tells whether the data or its processing was changed by others since this
     * instance was loaded.
     *
     * @internal
     */
    public function getDataState(): string
    {
        return self::buildDataState($this->getCustomSettings());
    }

    /**
     * Returns the state of the data (see getDataState()) as currently stored in the database, which differs from the
     * one of this instance if the asset was saved by others since it was loaded, and locks the asset against
     * concurrent saves until the end of the current transaction, so that the state can't change until the asset is
     * saved within this transaction. Must be called within a transaction.
     *
     * @internal
     */
    public function getStoredDataStateForUpdate(): string
    {
        $storedSettings = $this->getDao()->getDataBoundFieldsForUpdate()['customSettings'];

        return self::buildDataState($storedSettings);
    }

    /**
     * @param array<string, mixed> $customSettings
     */
    private static function buildDataState(array $customSettings): string
    {
        return implode('|', [
            self::normalizeDataGeneration($customSettings[self::CUSTOM_SETTING_DATA_GENERATION] ?? null) ?? '',
            ($customSettings[self::CUSTOM_SETTING_PROCESSING_PENDING] ?? null) ? '1' : '',
            self::normalizeDataGeneration($customSettings[self::CUSTOM_SETTING_PROCESSING_REVISION] ?? null) ?? '',
        ]);
    }

    private static function normalizeDataGeneration(mixed $dataGeneration): ?string
    {
        return is_scalar($dataGeneration) && $dataGeneration ? (string) $dataGeneration : null;
    }

    /**
     * @return $this
     */
    public function setDataChanged(bool $changed = true): static
    {
        $this->dataChanged = $changed;

        return $this;
    }

    public function getVersions(): array
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
    public function setVersions(array $versions): static
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @internal
     *
     * @param bool $keep whether to delete this file on shutdown or not
     *
     * @throws Exception
     */
    public function getTemporaryFile(bool $keep = false): string
    {
        return self::getTemporaryFileFromStream($this->getStream(), $keep);
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function getLocalFile(): string
    {
        return self::getLocalFileFromStream($this->getStream());
    }

    private function refreshCustomSettings(): void
    {
        if ($this->customSettingsNeedRefresh === true) {
            $customSettings = $this->getDao()->getCustomSettings();
            $this->setCustomSettings($customSettings);
            $this->customSettingsNeedRefresh = false;
        }
    }

    /**
     * @return $this
     */
    public function setCustomSetting(string $key, mixed $value): static
    {
        $this->refreshCustomSettings();
        $this->customSettings[$key] = $value;

        return $this;
    }

    public function getCustomSetting(string $key): mixed
    {
        $this->refreshCustomSettings();

        return $this->customSettings[$key] ?? null;
    }

    public function removeCustomSetting(string $key): void
    {
        $this->refreshCustomSettings();
        unset($this->customSettings[$key]);
    }

    public function getCustomSettings(): array
    {
        $this->refreshCustomSettings();

        return $this->customSettings;
    }

    /**
     * @return $this
     */
    public function setCustomSettings(mixed $customSettings): static
    {
        if (
            is_string($customSettings) &&
            $customSettings !== ''
        ) {
            if (strlen($customSettings) > 10e6) {
                $this->customSettingsCanBeCached = false;
            }
            $customSettings = Serialize::fromJson($customSettings);
        }

        if ($customSettings instanceof stdClass) {
            $customSettings = (array)$customSettings;
        }

        if (!is_array($customSettings)) {
            $customSettings = [];
        }

        $this->customSettings = $customSettings;
        // explicitly set custom settings are authoritative, so they must not be replaced by the ones from the database
        $this->customSettingsNeedRefresh = false;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimetype;
    }

    /**
     * @return $this
     */
    public function setMimeType(string $mimetype): static
    {
        $this->mimetype = $mimetype;

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
    public function setMetadataRaw(array $metadata): static
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
    public function setMetadata(array $metadata): static
    {
        $this->metadata = [];
        $this->setHasMetaData(false);
        if (!empty($metadata)) {
            foreach ($metadata as $metaItem) {
                $metaItem = (array)$metaItem; // also allow object with appropriate keys
                $this->addMetadata($metaItem['name'], $metaItem['type'], $metaItem['data'] ?? null, $metaItem['language'] ?? null);
            }
        }

        return $this;
    }

    public function getHasMetaData(): bool
    {
        return $this->hasMetaData;
    }

    /**
     * @return $this
     */
    public function setHasMetaData(bool $hasMetaData): static
    {
        $this->hasMetaData = $hasMetaData;

        return $this;
    }

    /**
     * @param string $type can be "asset", "checkbox", "date", "document", "input", "object", "select" or "textarea"
     *
     * @return $this
     */
    public function addMetadata(string $name, string $type, mixed $data = null, ?string $language = null): static
    {
        if ($name && $type) {
            $tmp = [];
            $name = str_replace('~', '---', $name);

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
     * @return $this
     */
    public function removeMetadata(string $name, ?string $language = null): static
    {
        if ($name) {
            $tmp = [];
            $name = str_replace('~', '---', $name);

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

    public function getMetadata(?string $name = null, ?string $language = null, bool $strictMatchLanguage = false, bool $raw = false): mixed
    {
        $preEvent = new AssetEvent($this);
        $preEvent->setArgument('metadata', $this->metadata);
        $this->dispatchEvent($preEvent, AssetEvents::PRE_GET_METADATA);
        $this->metadata = $preEvent->getArgument('metadata');

        if ($name) {
            return $this->getMetadataByName($name, $language, $strictMatchLanguage, $raw);
        }

        $metaData = $this->getObjectVar('metadata');
        $result = [];
        $metaDataWithLanguage = [];
        if (is_array($metaData)) {
            foreach ($metaData as $md) {
                $md = (array)$md;

                if ((empty($md['language']) && !$strictMatchLanguage) || ($language == $md['language']) || !$language) {
                    if (!$raw) {
                        $md['data'] = $this->transformMetadata($md);
                    }
                    $result[] = $md;
                }

                if (!empty($md['language'])) {
                    $metaDataWithLanguage[$md['language']][$md['name']] = $md;
                }
            }
        }

        if ($language && !$strictMatchLanguage) {
            foreach ($result as $key => &$item) {
                if (!$item['language'] && isset($metaDataWithLanguage[$language][$item['name']])) {
                    $itemWithLanguage = $metaDataWithLanguage[$language][$item['name']];
                    if (!in_array($itemWithLanguage, $result)) {
                        $item = $itemWithLanguage;
                    } else {
                        unset($result[$key]);
                    }
                }
            }
        }

        return $result;
    }

    private function transformMetadata(array $metaData): mixed
    {
        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
        $transformedData = $metaData['data'];

        try {
            /** @var Data $instance */
            $instance = $loader->build($metaData['type']);
            $transformedData = $instance->transformGetterData($metaData['data'], $metaData);
        } catch (UnsupportedException $e) {
            Logger::error((string) $e);
        }

        return $transformedData;
    }

    protected function getMetadataByName(
        string $name,
        ?string $language = null,
        bool $strictMatchLanguage = false,
        bool $raw = false
    ): mixed {
        $result = null;
        $data = null;
        if ($language === null) {
            $language = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
        }

        foreach ($this->metadata as $md) {
            if ($md['name'] == $name) {
                if ($language == $md['language'] || (empty($md['language']) && !$strictMatchLanguage)) {
                    $data = $md;

                    break;
                }
            }
        }

        if ($data) {
            $result = $raw ? $data : $this->transformMetadata($data);
        }

        return $result;
    }

    public function getFileSize(bool $formatted = false, int $precision = 2): int|string
    {
        try {
            $bytes = Storage::get('asset')->fileSize($this->getRealFullPath());
        } catch (Exception $e) {
            Logger::error('Unable to determine the file size of asset ' . $this->getRealFullPath() . ': ' . $e);
            $bytes = 0;
        }

        if ($formatted) {
            return formatBytes($bytes, $precision);
        }

        return $bytes;
    }

    public function getParent(): ?Asset
    {
        $parent = parent::getParent();

        return $parent instanceof Asset ? $parent : null;
    }

    public function setParent(?ElementInterface $parent): static
    {
        /** @var Pimcore\Model\Element\AbstractElement $parent */
        $this->parent = $parent;
        if ($parent instanceof Asset) {
            $this->parentId = $parent->getId();
        }

        return $this;
    }

    public function __wakeup(): void
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

        if ($this->isInDumpState()) {
            // a dump (e.g. version, recycle bin) contains the custom settings of the dumped state, which must not be
            // replaced by the current custom settings of the asset in the database (which don't even exist anymore
            // for a deleted asset). This also applies to dumps that don't contain the custom settings (see below),
            // as the current custom settings don't belong to the dumped state either.
            $this->customSettingsNeedRefresh = false;

            if ($this->customSettingsIncomplete === null
                && $this->customSettingsCanBeCached === false
                && $this->customSettings === []
            ) {
                // dump created before it was tracked whether the custom settings were loaded when the asset was
                // dumped: an asset hydrated from the cache without its custom settings (as they were too large for
                // the cache) was dumped without them, as they were not loaded explicitly before dumping (see
                // Asset\Service::loadAllFields()). Custom settings that are too large for the cache can't be empty
                // once they are loaded, so this state is detectable (but not distinguishable from custom settings
                // that were cleared after loading them, which is why it is tracked explicitly now).
                $this->customSettingsIncomplete = true;
            }
            // any other dump created before this was tracked stays unknown (null), see restoreStream()
        } elseif ($this->customSettingsCanBeCached === false) {
            $this->customSettingsNeedRefresh = true;
        }

        $this->setInDumpState(false);
    }

    public function __destruct()
    {
        // close open streams
        $this->closeStream();
    }

    public function resolveDependencies(): array
    {
        if (!Config::getSystemConfiguration()['dependency']['enabled']) {
            return [];
        }
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
                    } catch (UnsupportedException) {
                        //nothing to log here
                    }
                }
            }
        }

        return array_merge(...$dependencies);
    }

    public function __clone(): void
    {
        parent::__clone();
        $this->parent = null;
        $this->versions = null;
        $this->siblings = null;
        $this->scheduledTasks = null;
        // the stream is shared with the original asset, so it must not be closed here (which would close it for the
        // original asset as well, which would then fall back to the data in the storage and lose data that was
        // assigned but not saved yet, e.g. when it is cloned for a version or a recycle bin item), but just dropped
        $this->stream = null;
        $this->streamIsPlaceholder = false;
    }

    public function clearThumbnails(bool $force = false): void
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
     * Moves a directory (a folder's subtree) on the given storage: attempts the native
     * move first (atomic and cheap where the adapter supports it, e.g. on the local
     * filesystem) and falls back to relocating the contents file by file.
     *
     * Whether the fallback is needed is decided by what is actually left at the old
     * path, never by the move() outcome alone: on some S3-compatible storages a
     * directory is exposed as a copyable zero-byte object at its bare key, so move()
     * reports success after relocating just that object while the entire subtree
     * stays at the old prefix.
     *
     * @throws FilesystemException
     */
    private function moveDirectoryOnStorage(FilesystemOperator $storage, string $oldPath): void
    {
        try {
            $storage->move($oldPath, $this->getRealFullPath());
        } catch (UnableToMoveFile) {
            // expected on storages without native directory rename - covered by the fallback below
        }

        if ($this->isStorageOperationQueueEnabled()) {
            // the queue-aware adapter owns the move: a returned move() is either a real native
            // rename or a queued operation whose source content must stay in place until the
            // processor drains it - the fallback must not touch it
            return;
        }

        if ($storage->directoryExists($oldPath)) {
            //update children, if the parent move did not (fully) relocate them
            $this->updateChildPaths($storage, $oldPath);
        }
    }

    private function isStorageOperationQueueEnabled(): bool
    {
        return (bool) (Config::getSystemConfiguration('assets')['storage_operation_queue']['enabled'] ?? false);
    }

    /**
     * @throws FilesystemException
     */
    private function updateChildPaths(
        FilesystemOperator $storage,
        string $oldPath,
        ?string $newPath = null,
        bool $skipError = false
    ): void {
        if ($newPath === null) {
            $newPath = $this->getRealFullPath();
        }

        try {
            $movedFiles = [];
            $children = $storage->listContents($oldPath, true);
            $totalFiles = 0;

            /** @var \League\Flysystem\StorageAttributes $child */
            foreach ($children as $child) {
                if ($child instanceof \League\Flysystem\FileAttributes) {
                    ++$totalFiles;
                    $src  = $child['path'];
                    $dest = $newPath . substr('/' . $src, strlen($oldPath));

                    $storage->move($src, $dest);
                    $movedFiles[$dest] = $src;
                }
            }

            if ($totalFiles > 0) {
                $movedCount = count($movedFiles);

                if ($movedCount === $totalFiles) {
                    $storage->deleteDirectory($oldPath);
                } else {
                    \Pimcore\Logger::info(
                        sprintf(
                            'Moved %d/%d files from %s to %s. No exception was thrown for %d files,
                            so the source directory was not deleted.',
                            $movedCount, $totalFiles, $oldPath, $newPath, $totalFiles - $movedCount
                        )
                    );
                }
            }
        } catch (Throwable $e) {
            Logger::error(sprintf('Asset Move to %s failed: %s', $newPath, $e->getMessage()));

            if ($skipError) {
                return;
            }

            // rollback moved files
            foreach ($movedFiles as $src => $dest) {
                $storage->move($src, $dest);
            }

            // trigger database rollback
            throw $e;
        }
    }

    /**
     * @throws FilesystemException
     */
    private function relocateThumbnails(string $oldPath): void
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
                $this->moveThumbnailDirectoryOnStorage(Storage::get($storageName), $oldThumbnailsPath, $newThumbnailsPath);
            }
        }
    }

    /**
     * Moves one asset's (or folder's) thumbnail directory on the given storage - same
     * post-condition approach as moveDirectoryOnStorage(): the per-file fallback runs
     * based on what is left at the old path, not on the move() outcome; with the storage
     * operation queue enabled the adapter owns the operation instead.
     *
     * @throws FilesystemException
     */
    private function moveThumbnailDirectoryOnStorage(
        FilesystemOperator $storage,
        string $oldThumbnailsPath,
        string $newThumbnailsPath
    ): void {
        try {
            $this->moveThumbnailPath($storage, $oldThumbnailsPath, $newThumbnailsPath);
        } catch (UnableToMoveFile) {
            // expected on storages without native directory rename - covered by the fallback below
        }

        if (!$this->isStorageOperationQueueEnabled() && $storage->directoryExists($oldThumbnailsPath)) {
            //update children, if the parent move did not (fully) relocate them
            //if there is an error, we can ignore it
            $this->updateChildPaths($storage, $oldThumbnailsPath, $newThumbnailsPath, true);
        }
    }

    /**
     * Moves a thumbnail directory, checking alternate Unicode normalization forms of the
     * source path if the literal one doesn't exist. This covers cases where the DB-stored
     * path and the on-disk directory name ended up in different Unicode normalization forms
     * - e.g. because the original folder/file name was created on a macOS client, which
     * reports accented names in decomposed (NFD) form, while other parts of the stack
     * normalize to precomposed (NFC).
     *
     * The existing source is established via directoryExists() before moving, rather than
     * moving each candidate in turn and reacting to UnableToMoveFile: that exception also
     * covers destination, permission and backend failures, not just a missing source, so
     * catching it to decide "try the next Unicode form" could otherwise move an unrelated,
     * coincidentally-present legacy-form directory into $newPath on an unrelated failure.
     *
     * @throws UnableToMoveFile
     */
    private function moveThumbnailPath(FilesystemOperator $storage, string $oldPath, string $newPath): void
    {
        $candidates = array_unique(array_filter([
            $oldPath,
            Normalizer::normalize($oldPath, Normalizer::FORM_C) ?: null,
            Normalizer::normalize($oldPath, Normalizer::FORM_D) ?: null,
        ]));

        $source = $oldPath;
        foreach ($candidates as $candidate) {
            if ($storage->directoryExists($candidate)) {
                $source = $candidate;

                break;
            }
        }

        // None of the Unicode-form candidates exist (e.g. no thumbnails were ever
        // generated) - move the literal requested path so the caller sees the normal
        // "nothing to move" failure rather than one masked by this fallback.
        $storage->move($source, $newPath);
    }

    private function clearFolderThumbnails(Asset $asset): void
    {
        do {
            if ($asset instanceof Folder) {
                $asset->clearThumbnails(true);
            }

            $asset = $asset->getParent();
        } while ($asset !== null);
    }

    public function clearThumbnail(string $name): void
    {
        try {
            Storage::get('thumbnail')->deleteDirectory($this->getRealPath().'/'.$this->getId().'/image-thumb__'.$this->getId().'__'.$name);
            $this->getDao()->deleteFromThumbnailCache($name);
        } catch (Exception $e) {
            // noting to do
        }
    }

    /**
     * Adds a task to the asset update tasks queue which processes the asset in any case, e.g. to process it again
     * on demand (see triggerUpdateTask()), unless the previous processing failed
     *
     * @internal
     * public because it's also used by pimcore/admin-ui-classic-bundle
     */
    public function addToUpdateTaskQueue(): void
    {
        if (!$this->getCustomSetting(self::CUSTOM_SETTING_PROCESSING_FAILED)) {
            $this->triggerUpdateTask();
        }
    }

    /**
     * Adds a task to the asset update tasks queue which processes the asset in any case: it processes the state
     * the asset has when the task is handled, regardless of what happened to the asset in the meantime (in contrast
     * to the tasks created for replaced or restored data when saving, see addUpdateTaskForCurrentData())
     *
     * @internal
     */
    public function triggerUpdateTask(): void
    {
        /** @var LockInterface $lock */
        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($this->getUpdateQueueLockId());
        if ($lock->acquire()) {
            $bus = Pimcore::getContainer()->get('messenger.bus.pimcore-core');
            $message = new AssetUpdateTasksMessage($this->getId());

            $bus->dispatch($message);
        }
    }

    /**
     * Adds a task to the asset update tasks queue which processes the current data (whose processing is pending, see
     * isProcessingPending()) or only generates its previews. The task is bound to the current data (see
     * getDataGeneration()): it is skipped if the data is replaced or restored before the task is handled, as the new
     * data has its own task (whose results the task must not overwrite) or doesn't need any processing (processing it
     * anyway could overwrite the restored derived data). As such a task is only valid for its own data, it must never
     * be suppressed in favour of a task created for previous data, which is why the lock of triggerUpdateTask() isn't
     * used here.
     */
    private function addUpdateTaskForCurrentData(bool $previewsOnly): void
    {
        $dataGeneration = $this->getDataGeneration();
        if ($dataGeneration === null) {
            return;
        }

        $bus = Pimcore::getContainer()->get('messenger.bus.pimcore-core');
        $bus->dispatch(new AssetUpdateTasksMessage($this->getId(), $dataGeneration, $previewsOnly));
    }

    /**
     * @internal
     */
    public function getUpdateQueueLockId(): string
    {
        return 'asset-update-queue-' . $this->getId();
    }

    public function getFrontendPath(): string
    {
        $path = $this->getFullPath();
        if (!preg_match('@^(https?|data):@', $path)) {
            $path = \Pimcore\Tool::getHostUrl() . $path;
        }

        return $path;
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function addThumbnailFileToCache(string $localFile, string $filename, ThumbnailConfig $config): void
    {
        //try to get the dimensions with getimagesize because it is much faster than e.g. the Imagick-Adapter
        if ($imageSize = @getimagesize($localFile)) {
            $dimensions = [
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            ];
        } else {
            //fallback to Default Adapter
            $image = \Pimcore\Image::getInstance();
            if ($image->load($localFile)) {
                $dimensions = [
                    'width' => $image->getWidth(),
                    'height' => $image->getHeight(),
                ];
            }
        }

        if (!empty($dimensions)) {
            $this->getDao()->addToThumbnailCache(
                $config->getName(),
                $filename,
                filesize($localFile),
                $dimensions['width'],
                $dimensions['height']
            );
        }
    }
}
