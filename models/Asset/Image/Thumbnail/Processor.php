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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore;
use Pimcore\Config as PimcoreConfig;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Image\Adapter;
use Pimcore\Logger;
use Pimcore\Messenger\OptimizeImageMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Storage;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
class Processor
{
    use TemporaryFileHelperTrait;

    protected static array $argumentMapping = [
        'resize' => ['width', 'height'],
        'scaleByWidth' => ['width', 'forceResize'],
        'scaleByHeight' => ['height', 'forceResize'],
        'contain' => ['width', 'height', 'forceResize'],
        'cover' => ['width', 'height', 'positioning', 'forceResize'],
        'frame' => ['width', 'height', 'forceResize'],
        'trim' => ['tolerance'],
        'rotate' => ['angle'],
        'crop' => ['x', 'y', 'width', 'height'],
        'setBackgroundColor' => ['color'],
        'roundCorners' => ['width', 'height'],
        'setBackgroundImage' => ['path', 'mode'],
        'addOverlay' => ['path', 'x', 'y', 'alpha', 'composite', 'origin'],
        'addOverlayFit' => ['path', 'composite'],
        'applyMask' => ['path'],
        'cropPercent' => ['width', 'height', 'x', 'y'],
        'grayscale' => [],
        'sepia' => [],
        'sharpen' => ['radius', 'sigma', 'amount', 'threshold'],
        'gaussianBlur' => ['radius', 'sigma'],
        'brightnessSaturation' => ['brightness', 'saturation', 'hue'],
        'mirror' => ['mode'],
    ];

    private static function getAllowedFormat(string $format, array $allowed = [], string $fallback = 'png'): string
    {
        $typeMappings = [
            'jpg' => 'jpeg',
            'tif' => 'tiff',
        ];

        if (isset($typeMappings[$format])) {
            $format = $typeMappings[$format];
        }

        if (in_array($format, $allowed)) {
            $target = $format;
        } else {
            $target = $fallback;
        }

        return $target;
    }

    /**
     * @param string|resource|null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     *
     * @throws Exception
     */
    public static function process(
        Asset $asset,
        Config $config,
        mixed $fileSystemPath = null,
        bool $deferred = false,
        bool &$generated = false
    ): array {
        $generated = false;
        $format = strtolower($config->getFormat());
        // Optimize if allowed to strip info.
        $optimizeContent = (!$config->isPreserveColor() && !$config->isPreserveMetaData());
        $optimizedFormat = false;

        if (self::containsTransformationType($config, '1x1_pixel')) {
            return [
                'src' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                'type' => 'data-uri',
            ];
        }

        $fileExt = pathinfo($asset->getFilename(), PATHINFO_EXTENSION);

        // simple detection for source type if SOURCE is selected
        if ($format == 'source' || empty($format)) {
            $optimizedFormat = true;
            $format = self::getAllowedFormat($fileExt, ['pjpeg', 'jpeg', 'gif', 'png'], 'png');
            if ($format === 'jpeg') {
                $format = 'pjpeg';
            }
        }

        if ($format == 'print') {
            // Don't optimize images for print as we assume we want images as
            // untouched as possible.
            $optimizedFormat = $optimizeContent = false;
            $format = self::getAllowedFormat($fileExt, ['svg', 'jpeg', 'png', 'tiff'], 'png');

            if (($format == 'tiff') && \Pimcore\Tool::isFrontendRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = 'png';
                $deferred = false; // deferred is default, but it's not possible when using isFrontendRequestByAdmin()
            } elseif (
                ($format == 'tiff' && self::containsTransformationType($config, 'tifforiginal'))
                || $format == 'svg'
            ) {
                return [
                    'src' => $asset->getRealFullPath(),
                    'type' => 'asset',
                ];
            }
        } elseif ($format == 'tiff') {
            $optimizedFormat = $optimizeContent = false;
            if (\Pimcore\Tool::isFrontendRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = 'png';
                $deferred = false; // deferred is default, but it's not possible when using isFrontendRequestByAdmin()
            }
        }

        $image = Asset\Image::getImageTransformInstance();
        $thumbDir = rtrim($asset->getRealPath(), '/').'/'.$asset->getId().'/image-thumb__'.$asset->getId().'__'.$config->getName();
        $filename = preg_replace("/\." . preg_quote(pathinfo($asset->getFilename(), PATHINFO_EXTENSION), '/') . '$/i', '', $asset->getFilename());

        // add custom suffix if available
        if ($config->getFilenameSuffix()) {
            $filename .= '~-~' . $config->getFilenameSuffix();
        }
        // add high-resolution modifier suffix to the filename
        if ($config->getHighResolution() > 1) {
            $filename .= '@' . $config->getHighResolution() . 'x';
        }

        $fileExtension = $format;
        if ($format == 'original') {
            $fileExtension = $fileExt;
        } elseif ($format === 'pjpeg' || $format === 'jpeg') {
            $fileExtension = 'jpg';
        }

        $filename .= '.' . $config->getHash([$asset->getChecksum()]) . '.'. $fileExtension;

        $storagePath = $thumbDir . '/' . $filename;
        $storage = Storage::get('thumbnail');

        // check for existing and still valid thumbnail

        $modificationDate = null;
        $statusCacheEnabled = PimcoreConfig::getSystemConfiguration('assets')['image']['thumbnails']['status_cache'];
        if ($statusCacheEnabled && $deferred) {
            $modificationDate = $asset->getDao()->getCachedThumbnailModificationDate($config->getName(), $filename);
        } else {
            try {
                $modificationDate = $storage->lastModified($storagePath);
            } catch (FilesystemException $e) {
                // nothing to do
            }
        }

        if ($modificationDate) {
            try {
                if ($modificationDate >= $asset->getDataModificationDate()) {
                    return [
                        'src' => $storagePath,
                        'type' => 'thumbnail',
                        'storagePath' => $storagePath,
                    ];
                } else {
                    // delete the file if it's not valid anymore, otherwise writing the actual data from
                    // the local tmp-file to the real storage a bit further down doesn't work, as it has a
                    // check for race-conditions & locking, so it needs to check for the existence of the thumbnail
                    $storage->delete($storagePath);

                    // refresh the thumbnail cache, if the asset modification date is modified
                    // this is necessary because the thumbnail cache is not cleared automatically
                    // when the original asset is modified
                    $asset->getDao()->deleteFromThumbnailCache($config->getName());
                }
            } catch (FilesystemException $e) {
                // nothing to do
            }
        }

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in
        // \Pimcore\Bundle\CoreBundle\Controller\PublicServicesController::thumbnailAction()
        // so that it can be used also with dynamic configurations
        $pathInfo = ltrim($asset->getRealPath(), '/') . $asset->getId() . '/' . $config->getName() . '/' . $filename;
        $tmpStoreDeferredConfigId = 'thumb_' . $asset->getId() . '__' . md5($pathInfo);
        if ($deferred) {
            // only add the config to the TmpStore if necessary (e.g. if the config is auto-generated)
            if (!Config::exists($config->getName())) {
                TmpStore::add($tmpStoreDeferredConfigId, $config, 'thumbnail_deferred');
            }

            return [
                'src' => $storagePath,
                'type' => 'deferred',
                'storagePath' => $storagePath,
            ];
        }

        // transform image
        $image->setPreserveColor($config->isPreserveColor());
        $image->setPreserveMetaData($config->isPreserveMetaData());
        $image->setPreserveAnimation($config->getPreserveAnimation());

        $fileExists = false;

        try {
            // check if file is already on the file-system and if it is still valid
            $modificationDate = $storage->lastModified($storagePath);
            if ($modificationDate < $asset->getModificationDate()) {
                $storage->delete($storagePath);
            } else {
                $fileExists = true;
            }
        } catch (Exception $e) {
            Logger::debug($e->getMessage());
        }

        if ($fileExists === false) {
            $lockKey = 'image_thumbnail_' . $asset->getId() . '_' . md5($storagePath);
            $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($lockKey);

            $lock->acquire(true);

            $startTime = microtime(true);

            // after we got the lock, check again if the image exists in the meantime - if not - generate it
            if (!$storage->fileExists($storagePath)) {
                // all checks on the file system should be below the deferred part for performance reasons (remote file systems)
                if (!$fileSystemPath) {
                    $fileSystemPath = $asset->getLocalFile();
                }

                if (is_resource($fileSystemPath)) {
                    $fileSystemPathStream = $fileSystemPath;
                    $fileSystemPath = self::getLocalFileFromStream($fileSystemPath);
                    @fclose($fileSystemPathStream);
                }

                if (!file_exists($fileSystemPath)) {
                    throw new Exception(sprintf('Source file %s does not exist!', $fileSystemPath));
                }

                if (!$image->load($fileSystemPath, ['asset' => $asset])) {
                    throw new Exception(sprintf('Unable to generate thumbnail for asset %s from source image %s', $asset->getId(), $fileSystemPath));
                }

                $transformations = $config->getItems();

                // check if the original image has an orientation exif flag
                // if so add a transformation at the beginning that rotates and/or mirrors the image
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($fileSystemPath);
                    if (is_array($exif)) {
                        if (array_key_exists('Orientation', $exif)) {
                            $orientation = (int)$exif['Orientation'];

                            if ($orientation > 1) {
                                $angleMappings = [
                                    2 => 180,
                                    3 => 180,
                                    4 => 180,
                                    5 => 90,
                                    6 => 90,
                                    7 => 90,
                                    8 => 270,
                                ];

                                if (array_key_exists($orientation, $angleMappings)) {
                                    array_unshift($transformations, [
                                        'method' => 'rotate',
                                        'arguments' => [
                                            'angle' => $angleMappings[$orientation],
                                        ],
                                    ]);
                                }

                                // values that have to be mirrored, this is not very common, but should be covered anyway
                                $mirrorMappings = [
                                    2 => 'vertical',
                                    4 => 'horizontal',
                                    5 => 'vertical',
                                    7 => 'horizontal',
                                ];

                                if (array_key_exists($orientation, $mirrorMappings)) {
                                    array_unshift($transformations, [
                                        'method' => 'mirror',
                                        'arguments' => [
                                            'mode' => $mirrorMappings[$orientation],
                                        ],
                                    ]);
                                }
                            }
                        }
                    }
                }

                self::applyTransformations($image, $asset, $config, $transformations);

                if ($optimizedFormat) {
                    $format = $image->getContentOptimizedFormat();
                }

                $tmpFsPath = File::getLocalTempFilePath($fileExtension);
                $image->save($tmpFsPath, $format, $config->getQuality());
                $stream = fopen($tmpFsPath, 'rb');
                $storage->writeStream($storagePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($statusCacheEnabled && $asset instanceof Asset\Image) {
                    //update thumbnail dimensions to cache
                    $asset->addThumbnailFileToCache($tmpFsPath, $filename, $config);
                }

                if (!Config::exists($config->getName())) {
                    // delete dynamic thumbnail configs out of the TmpStore as soon as we've generated the thumbnail file
                    TmpStore::delete($tmpStoreDeferredConfigId);
                }

                $generated = true;

                $isImageOptimizersEnabled = PimcoreConfig::getSystemConfiguration('assets')['image']['thumbnails']['image_optimizers']['enabled'];
                if ($optimizedFormat && $optimizeContent && $isImageOptimizersEnabled) {
                    Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                        new OptimizeImageMessage($storagePath)
                    );
                }

                Logger::debug('Thumbnail ' . $storagePath . ' generated in ' . (microtime(true) - $startTime) . ' seconds');
            } else {
                Logger::debug('Thumbnail ' . $storagePath . ' already generated, waiting on lock for ' . (microtime(true) - $startTime) . ' seconds');
            }
            $lock->release();
        }

        // quick bugfix / workaround, it seems that imagemagick / image optimizers creates sometimes empty PNG chunks (total size 33 bytes)
        // no clue why it does so as this is not continuous reproducible, and this is the only fix we can do for now
        // if the file is corrupted the file will be created on the fly when requested by the browser (because it's deleted here)
        if ($storage->fileExists($storagePath) && $storage->fileSize($storagePath) < 50) {
            $storage->delete($storagePath);
            $asset->getDao()->deleteFromThumbnailCache($config->getName(), $filename);

            return [
                'src' => $storagePath,
                'type' => 'deferred',
            ];
        }

        return [
            'src' => $storagePath,
            'type' => 'thumbnail',
            'storagePath' => $storagePath,
        ];
    }

    private static function applyTransformations(Adapter $image, Asset $asset, Config $config, ?array $transformations): void
    {
        if ($transformations) {
            $sourceImageWidth = PHP_INT_MAX;
            $sourceImageHeight = PHP_INT_MAX;
            if ($asset instanceof Asset\Image) {
                $sourceImageWidth = $asset->getWidth();
                $sourceImageHeight = $asset->getHeight();
            }

            $highResFactor = $config->getHighResolution();
            $imageCropped = false;

            $calculateMaxFactor = function ($factor, $original, $new) {
                $newFactor = $factor * $original / $new;
                if ($newFactor < 1) {
                    // don't go below factor 1
                    $newFactor = 1;
                }

                return $newFactor;
            };

            // sorry for the goto/label - but in this case it makes life really easier and the code more readable
            prepareTransformations:

            foreach ($transformations as &$transformation) {
                if (!empty($transformation) && !isset($transformation['isApplied'])) {
                    $arguments = [];

                    $mapping = self::$argumentMapping[$transformation['method']];

                    if (is_array($transformation['arguments'])) {
                        foreach ($transformation['arguments'] as $key => $value) {
                            $position = array_search($key, $mapping);
                            if ($position !== false) {
                                // high res calculations if enabled
                                if (!in_array($transformation['method'], ['cropPercent']) && in_array($key,
                                    ['width', 'height', 'x', 'y'])) {
                                    if ($highResFactor && $highResFactor > 1) {
                                        $value *= $highResFactor;
                                        $value = (int)ceil($value);

                                        if (!isset($transformation['arguments']['forceResize']) || !$transformation['arguments']['forceResize']) {
                                            // check if source image is big enough otherwise adjust the high-res factor
                                            if (in_array($key, ['width', 'x'])) {
                                                if ($sourceImageWidth < $value) {
                                                    $highResFactor = $calculateMaxFactor(
                                                        $highResFactor,
                                                        $sourceImageWidth,
                                                        $value
                                                    );
                                                    goto prepareTransformations;
                                                }
                                            } elseif (in_array($key, ['height', 'y'])) {
                                                if ($sourceImageHeight < $value) {
                                                    $highResFactor = $calculateMaxFactor(
                                                        $highResFactor,
                                                        $sourceImageHeight,
                                                        $value
                                                    );
                                                    goto prepareTransformations;
                                                }
                                            }
                                        }
                                    }
                                }

                                // inject the focal point
                                if ($transformation['method'] == 'cover' && $key == 'positioning' && $asset->getCustomSetting('focalPointX')) {
                                    $value = [
                                        'x' => $asset->getCustomSetting('focalPointX'),
                                        'y' => $asset->getCustomSetting('focalPointY'),
                                    ];
                                }

                                $arguments[$position] = $value;
                            }
                        }
                    }

                    ksort($arguments);

                    if (method_exists($image, $transformation['method'])) {
                        call_user_func_array([$image, $transformation['method']], $arguments);
                    }

                    $transformation['isApplied'] = true;
                }
            }
        }
    }

    private static function containsTransformationType(Config $config, string $transformationType): bool
    {
        $transformations = $config->getItems();
        foreach ($transformations as $transformation) {
            if (!empty($transformation)) {
                if ($transformation['method'] == $transformationType) {
                    return true;
                }
            }
        }

        return false;
    }
}
