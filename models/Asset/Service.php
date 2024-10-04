<?php
declare(strict_types=1);

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

namespace Pimcore\Model\Asset;

use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ThumbnailConfig;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data;
use Pimcore\Model\Element;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Service extends Model\Element\Service
{
    /**
     * @internal
     *
     * @var array
     */
    public const GRID_SYSTEM_COLUMNS = ['preview', 'id', 'type', 'fullpath', 'filename', 'creationDate', 'modificationDate', 'size'];

    /**
     * @internal
     */
    protected ?Model\User $_user;

    /**
     * @internal
     */
    protected array $_copyRecursiveIds = [];

    public function __construct(Model\User $user = null)
    {
        $this->_user = $user;
    }

    /**
     * @return Asset|Folder|null copied asset
     *
     * @throws Exception
     */
    public function copyRecursive(Asset $target, Asset $source, bool $initial = true): Asset|Folder|null
    {
        // avoid recursion
        if ($initial) {
            $this->_copyRecursiveIds = [];
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return null;
        }

        $source->getProperties();

        // triggers actions before asset cloning
        $event = new AssetEvent($source, [
            'target_element' => $target,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        /** @var Asset $new */
        $new = Element\Service::cloneMe($source);
        $new->setObjectVar('id', null);
        if ($new instanceof Asset\Folder) {
            $new->setChildren(null);
        }

        $new->setFilename(Element\Service::getSafeCopyName($new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(null);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChildren() as $child) {
            $this->copyRecursive($new, $child, false);
        }

        if ($target instanceof Asset\Folder) {
            $this->updateChildren($target, $new);
        }

        // triggers actions after the complete asset cloning
        $event = new AssetEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::POST_COPY);

        return $new;
    }

    /**
     * @return Asset|Folder copied asset
     *
     * @throws Exception
     */
    public function copyAsChild(Asset $target, Asset $source): Asset|Folder
    {
        $source->getProperties();

        // triggers actions before asset cloning
        $event = new AssetEvent($source, [
            'target_element' => $target,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        /** @var Asset $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);

        if ($new instanceof Asset\Folder) {
            $new->setChildren(null);
        }
        $new->setFilename(Element\Service::getSafeCopyName($new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(null);
        $new->setCreationDate(time());
        $new->setStream($source->getStream());
        $new->save();

        if ($target instanceof Asset\Folder) {
            $this->updateChildren($target, $new);
        }

        // triggers actions after the complete asset cloning
        $event = new AssetEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::POST_COPY);

        return $new;
    }

    /**
     * @throws Exception
     */
    public function copyContents(Asset $target, Asset $source): Asset
    {
        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new Exception('Source and target have to be the same type');
        }

        // triggers actions before asset cloning
        $event = new AssetEvent($source, [
            'target_element' => $target,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        if (!$source instanceof Asset\Folder) {
            $target->setStream($source->getStream());
            $target->setCustomSettings($source->getCustomSettings());
        }

        $target->setUserModification($this->_user ? $this->_user->getId() : 0);
        $target->setProperties(self::cloneProperties($source->getProperties()));
        $target->save();

        return $target;
    }

    public static function pathExists(string $path, string $type = null): bool
    {
        if (!$path) {
            return false;
        }

        $path = Element\Service::correctPath($path);

        try {
            $asset = new Asset();

            if (self::isValidPath($path, 'asset')) {
                $asset->getDao()->getByPath($path);

                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @internal
     */
    public static function loadAllFields(Element\ElementInterface $element): Element\ElementInterface
    {
        $element->getProperties();

        return $element;
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @internal
     */
    public static function rewriteIds(Asset $asset, array $rewriteConfig): Asset
    {
        // rewriting properties
        $properties = $asset->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $asset->setProperties($properties);

        return $asset;
    }

    /**
     * @internal
     */
    public static function minimizeMetadata(array $metadata, string $mode): array
    {
        $result = [];
        foreach ($metadata as $item) {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

            try {
                /** @var Data $instance */
                $instance = $loader->build($item['type']);

                if ($mode == 'grid') {
                    $transformedData = $instance->getDataFromListfolderGrid($item['data'] ?? null, $item);
                } else {
                    $transformedData = $instance->getDataFromEditMode($item['data'] ?? null, $item);
                }

                $item['data'] = $transformedData;
            } catch (UnsupportedException $e) {
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @internal
     */
    public static function expandMetadataForEditmode(array $metadata): array
    {
        $result = [];
        foreach ($metadata as $item) {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            $transformedData = $item['data'];

            try {
                /** @var Data $instance */
                $instance = $loader->build($item['type']);
                $transformedData = $instance->getDataForEditMode($item['data'], $item);
            } catch (UnsupportedException $e) {
            }

            $item['data'] = $transformedData;
            //get the config from an predefined property-set (eg. select)
            $predefined = Model\Metadata\Predefined::getByName($item['name']);
            if ($predefined && $predefined->getType() == $item['type'] && $predefined->getConfig()) {
                $item['config'] = $predefined->getConfig();
            }

            $key = $item['name'] . '~' . $item['language'];
            $result[$key] = $item;
        }

        return $result;
    }

    public static function getUniqueKey(ElementInterface $element, int $nr = 0): string
    {
        $list = new Listing();
        $key = Element\Service::getValidKey($element->getKey(), 'asset');
        if (!$key) {
            throw new Exception('No item key set.');
        }
        if ($nr) {
            if ($element->getType() == 'folder') {
                $key = $key . '_' . $nr;
            } else {
                $keypart = substr($key, 0, strrpos($key, '.'));
                $extension = str_replace($keypart, '', $key);
                $key = $keypart . '_' . $nr . $extension;
            }
        }

        $parent = $element->getParent();
        if (!$parent) {
            throw new Exception('You have to set a parent folder to determine a unique Key');
        }

        if (!$element->getId()) {
            $list->setCondition('parentId = ? AND `filename` = ? ', [$parent->getId(), $key]);
        } else {
            $list->setCondition('parentId = ? AND `filename` = ? AND id != ? ', [$parent->getId(), $key, $element->getId()]);
        }
        $check = $list->loadIdList();
        if (!empty($check)) {
            $nr++;
            $key = self::getUniqueKey($element, $nr);
        }

        return $key;
    }

    /**
     * @throws Exception
     */
    public static function getImageThumbnailByArrayConfig(array $config): null|ThumbnailInterface|Asset\Video\ImageThumbnailInterface|Asset\Document\ImageThumbnailInterface|array
    {
        $asset = Asset::getById($config['asset_id']);

        if (!$asset) {
            return null;
        }

        $config['file_extension'] ??= strtolower(pathinfo($config['filename'], PATHINFO_EXTENSION));

        $prefix = preg_replace('@^cache-buster\-[\d]+\/@', '', $config['prefix']);
        $prefix = preg_replace('@' . $asset->getId() . '/$@', '', $prefix);
        if (ltrim($asset->getPath(), '/') === ltrim($prefix, '/')) {
            // just check if the thumbnail exists -> throws exception otherwise
            $thumbnailConfigClass = 'Pimcore\\Model\\Asset\\' . ucfirst($config['type']) . '\\Thumbnail\Config';
            $thumbnailConfig = $thumbnailConfigClass::getByName($config['thumbnail_name']);

            if (!$thumbnailConfig) {
                // check if there's an item in the TmpStore
                // remove an eventually existing cache-buster prefix first (eg. when using with a CDN)
                $pathInfo = preg_replace('@^cache-buster\-[\d]+/@', '', $config['prefix']) . $config['thumbnail_name'] . '/' . $config['filename'];
                $deferredConfigId = 'thumb_' . $config['asset_id'] . '__' . md5(urldecode($pathInfo));

                if ($thumbnailConfigItem = TmpStore::get($deferredConfigId)) {
                    $thumbnailConfig = $thumbnailConfigItem->getData();
                    // the dynamic config get's deleted out of the TmpStore in the Processor after the thumbnail
                    // file was generated, to avoid race conditions and other unintended behavior

                    if (!$thumbnailConfig instanceof $thumbnailConfigClass) {
                        throw new Exception('Deferred thumbnail config file doesn\'t contain a valid '.$thumbnailConfigClass.' object');
                    }
                } elseif (Config::getSystemConfiguration()['assets'][$config['type']]['thumbnails']['status_cache']) {
                    // Delete Thumbnail Name from Cache so the next call can generate a new TmpStore entry
                    // because otherwise it's getting directly delivered from the cache by Processor:process()
                    // without having the actual thumbnail file on the storage
                    $asset->getDao()->deleteFromThumbnailCache($config['thumbnail_name']);
                }
            }

            if (!$thumbnailConfig) {
                return null;
            }

            if ($config['type'] === 'image' && strcasecmp($thumbnailConfig->getFormat(), 'SOURCE') === 0) {
                $formatOverride = $config['file_extension'];
                if (in_array($config['file_extension'], ['jpg', 'jpeg'])) {
                    $formatOverride = 'pjpeg';
                }
                $thumbnailConfig->setFormat($formatOverride);
            }

            if ($asset instanceof Asset\Video) {
                if ($config['type'] === 'video') {
                    //for video thumbnails of videos, it returns an array
                    return $asset->getThumbnail($config['thumbnail_name'], [$config['file_extension']]);
                } else {
                    $time = 1;
                    if (preg_match("|~\-~time\-(\d+)\.|", $config['filename'], $matchesThumbs)) {
                        $time = (int)$matchesThumbs[1];
                    }

                    return $asset->getImageThumbnail($thumbnailConfig, $time);
                }
            } elseif ($asset instanceof Asset\Document) {
                $page = 1;
                if (preg_match("|~\-~page\-(\d+)(@[0-9.]+x)?\.|", $config['filename'], $matchesThumbs)) {
                    $page = (int)$matchesThumbs[1];
                }

                $thumbnailConfig->setName(preg_replace("/\-[\d]+/", '', $thumbnailConfig->getName()));
                $thumbnailConfig->setName(str_replace('document_', '', $thumbnailConfig->getName()));

                return $asset->getImageThumbnail($thumbnailConfig, $page);
            } elseif ($asset instanceof Asset\Image) {
                // Throw exception if the requested thumbnail format is disabled from the config
                $thumbnailFormats = ThumbnailConfig::getAutoFormats();
                if (!in_array($config['file_extension'], ['jpg', 'jpeg'])) {
                    if (empty($thumbnailFormats)) {
                        throw new NotFoundHttpException('Requested thumbnail format is disabled');
                    }
                    foreach ($thumbnailFormats as $autoFormat => $autoFormatConfig) {
                        if ($config['file_extension'] == $autoFormat && !$autoFormatConfig['enabled']) {
                            throw new NotFoundHttpException('Requested thumbnail format is disabled');
                        }
                    }

                    if (!empty($thumbnailFormats[$config['file_extension']]['quality'] ?? null)) {
                        $thumbnailConfig->setQuality($thumbnailFormats[$config['file_extension']]['quality']);
                    }
                }

                //check if high res image is called

                preg_match("@([^\@]+)(\@[0-9.]+x)?\.?([^\.]+)?\.([a-zA-Z]{2,5})@", $config['filename'], $matches);

                if (empty($matches) || !isset($matches[1])) {
                    return null;
                }

                if (array_key_exists(2, $matches) && $matches[2]) {
                    $highResFactor = (float)str_replace(['@', 'x'], '', $matches[2]);
                    $thumbnailConfig->setHighResolution($highResFactor);
                }

                // check if a media query thumbnail was requested
                if (preg_match("#~\-~media\-\-(.*)\-\-query#", $matches[1], $mediaQueryResult)) {
                    $thumbnailConfig->selectMedia($mediaQueryResult[1]);
                }

                return $asset->getThumbnail($thumbnailConfig);
            }
        }

        return null;
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    public static function getStreamedResponseFromImageThumbnail(
        ThumbnailInterface|Asset\Video\ImageThumbnailInterface|Asset\Document\ImageThumbnailInterface|array $thumbnail,
        array $config
    ): ?StreamedResponse {
        $thumbnailStream = null;

        $storage = Storage::get('thumbnail');
        $config['file_extension'] ??= strtolower(pathinfo($config['filename'], PATHINFO_EXTENSION));

        if ($config['type'] === 'image') {
            $thumbnailStream = $thumbnail->getStream();

            $mime = $thumbnail->getMimeType();
            $fileSize = $thumbnail->getFileSize();
            $pathReference = $thumbnail->getPathReference();
            $actualFileExtension = pathinfo($pathReference['src'], PATHINFO_EXTENSION);

            if ($actualFileExtension !== $config['file_extension']) {
                // create a copy/symlink to the file with the original file extension
                // this can be e.g. the case when the thumbnail is called as foo.png but the thumbnail config
                // is set to auto-optimized format so the resulting thumbnail can be jpeg
                $requestedFile = preg_replace('/\.' . $actualFileExtension . '$/', '.' . $config['file_extension'], $pathReference['src']);

                //Only copy the file if not exists yet
                if (!$storage->fileExists($requestedFile)) {
                    $storage->writeStream($requestedFile, $thumbnailStream);
                }

                //Stream can be closed by writeStream and needs to be reloaded.
                $thumbnailStream = $storage->readStream($requestedFile);
            }
        } elseif ($config['type'] === 'video') {
            $storagePath = urldecode($thumbnail['formats'][$config['file_extension']]);

            if ($storage->fileExists($storagePath)) {
                $thumbnailStream = $storage->readStream($storagePath);
            }
            $mime = $storage->mimeType($storagePath);
            $fileSize = $storage->fileSize($storagePath);
        } else {
            throw new Exception('Cannot determine mime type and file size of ' . $config['type'] . ' thumbnail, see logs for details.');
        }
        // set appropriate caching headers
        // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
        $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess

        $headers = [
            'Cache-Control' => 'public, max-age=' . $lifetime,
            'Expires' => date('D, d M Y H:i:s T', time() + $lifetime),
            'Content-Type' => $mime,
            'Content-Length' => $fileSize,
        ];

        $headers[AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER] = true;

        if ($thumbnailStream) {
            return new StreamedResponse(function () use ($thumbnailStream) {
                fpassthru($thumbnailStream);
            }, 200, $headers);
        }

        return $thumbnailStream;
    }

    /**
     * @throws Exception
     */
    public static function getStreamedResponseByUri(string $uri): ?StreamedResponse
    {
        $config = self::extractThumbnailInfoFromUri($uri);

        if ($config) {
            return self::getStreamedResponseForThumbnail($config, $uri);
        }

        return null;
    }

    /**
     * @internal
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public static function getStreamedResponseForThumbnail(array $config, string $uri): ?StreamedResponse
    {
        $storage = Storage::get('thumbnail');
        $storagePath = urldecode($uri);

        $prefix = \Pimcore\Config::getSystemConfiguration('assets')['frontend_prefixes']['thumbnail'];
        if ($prefix) {
            $storagePath = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $storagePath);
        }

        // thumbnail urls are at least 10 characters long
        if (strlen($uri) > 10 && $storage->fileExists($storagePath)) {
            $stream = $storage->readStream($storagePath);

            $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess

            return new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Cache-Control' => 'public, max-age=' . $lifetime,
                'Expires' => date('D, d M Y H:i:s T', time() + $lifetime),
                'Content-Type' => $storage->mimeType($storagePath),
                'Content-Length' => $storage->fileSize($storagePath),
            ]);
        } else {
            $thumbnail = Asset\Service::getImageThumbnailByArrayConfig($config);
            if ($thumbnail) {
                return Asset\Service::getStreamedResponseFromImageThumbnail($thumbnail, $config);
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public static function extractThumbnailInfoFromUri(string $uri): array
    {
        // See `_pimcore_service_thumbnail` in `CoreBundle\config\routing.yaml`

        $regExpression = sprintf('/(%s)(%s)-thumb__(%s)__(%s)\/(%s)/',
            '.*',               // prefix
            'video|image',      // type
            '\d+',              // assetId
            '[a-zA-Z0-9_\-]+',  // thumbnailName
            '.*'                // filename
        );

        if (preg_match($regExpression, $uri, $matches)) {
            return [
                'prefix' => $matches[1],
                'type' => $matches[2],
                'asset_id' => $matches[3],
                'thumbnail_name' => $matches[4],
                'filename' => $matches[5],
            ];
        } else {
            throw new Exception(sprintf('Uri `%s` is not valid and could not be parsed', $uri));
        }
    }
}
