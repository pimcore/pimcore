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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Logger;
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

    /**
     * @var array
     */
    protected static $argumentMapping = [
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

    /**
     * @param string $format
     * @param array $allowed
     * @param string $fallback
     *
     * @return string
     */
    private static function getAllowedFormat($format, $allowed = [], $fallback = 'png')
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
     * @param Asset $asset
     * @param Config $config
     * @param string|resource|null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @param bool $generated
     *
     * @return array
     */
    public static function process(Asset $asset, Config $config, $fileSystemPath = null, $deferred = false, &$generated = false)
    {
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

        if (!$fileSystemPath && $asset instanceof Asset) {
            $fileSystemPath = $asset->getLocalFile();
        }

        if (is_resource($fileSystemPath)) {
            $fileSystemPath = self::getLocalFileFromStream($fileSystemPath);
        }

        $fileExt = File::getFileExtension(basename($fileSystemPath));

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
        $thumbDir = rtrim($asset->getRealPath(), '/') . '/image-thumb__' . $asset->getId() . '__' . $config->getName();
        $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename()), '/') . '$/i', '', $asset->getFilename());

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
            $fileExtension = \Pimcore\File::getFileExtension($fileSystemPath);
        } elseif ($format === 'pjpeg' || $format === 'jpeg') {
            $fileExtension = 'jpg';
        }

        $filename .= '.' . $fileExtension;

        $storagePath = $thumbDir . '/' . $filename;
        $storage = Storage::get('thumbnail');

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in
        // \Pimcore\Bundle\CoreBundle\Controller\PublicServicesController::thumbnailAction()
        // so that it can be used also with dynamic configurations
        if ($deferred) {
            // only add the config to the TmpStore if necessary (e.g. if the config is auto-generated)
            if (!Config::exists($config->getName())) {
                $configId = 'thumb_' . $asset->getId() . '__' . md5($storagePath);
                TmpStore::add($configId, $config, 'thumbnail_deferred');
            }

            return [
                'src' => $storagePath,
                'type' => $storage->fileExists($storagePath) ? 'thumbnail' : 'deferred',
                'storagePath' => $storagePath,
            ];
        }

        // all checks on the file system should be below the deferred part for performance reasons (remote file systems)
        if (!file_exists($fileSystemPath)) {
            throw new \Exception(sprintf('Source file %s does not exist!', $fileSystemPath));
        }

        // check for existing and still valid thumbnail
        if ($storage->fileExists($storagePath) && $storage->lastModified($storagePath) >= $asset->getModificationDate()) {
            return [
                'src' => $storagePath,
                'type' => 'thumbnail',
                'storagePath' => $storagePath,
            ];
        }

        // transform image
        $image->setPreserveColor($config->isPreserveColor());
        $image->setPreserveMetaData($config->isPreserveMetaData());
        $image->setPreserveAnimation($config->getPreserveAnimation());

        if (!$image->load($fileSystemPath, ['asset' => $asset])) {
            throw new \Exception(sprintf('Unable to generate thumbnail for asset %s from source image %s', $asset->getId(), $fileSystemPath));
        }

        $startTime = microtime(true);

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

        if (is_array($transformations) && count($transformations) > 0) {
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

            foreach ($transformations as $transformation) {
                if (!empty($transformation)) {
                    $arguments = [];

                    if (is_string($transformation['method'])) {
                        $mapping = self::$argumentMapping[$transformation['method']];

                        if (in_array($transformation['method'], ['cropPercent'])) {
                            //avoid double cropping in case of $highResFactor re-calculation (goto prepareTransformations)
                            if ($imageCropped) {
                                continue;
                            }
                            $imageCropped = true;
                        }

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
                    }

                    ksort($arguments);
                    if (!is_string($transformation['method']) && is_callable($transformation['method'])) {
                        $transformation['method']($image);
                    } elseif (method_exists($image, $transformation['method'])) {
                        call_user_func_array([$image, $transformation['method']], $arguments);
                    }
                }
            }
        }

        if ($optimizedFormat) {
            $format = $image->getContentOptimizedFormat();
        }

        if (!$storage->fileExists($storagePath)) {
            $lockKey = 'image_thumbnail_' . $asset->getId() . '_' . md5($storagePath);
            $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($lockKey);

            $lock->acquire(true);

            // after we got the lock, check again if the image exists in the meantime - if not - generate it
            if (!$storage->fileExists($storagePath)) {
                $tmpFsPath = File::getLocalTempFilePath($fileExtension);
                $image->save($tmpFsPath, $format, $config->getQuality());
                $stream = fopen($tmpFsPath, 'rb');
                $storage->writeStream($storagePath, $stream);
                fclose($stream);
                unlink($tmpFsPath);

                $generated = true;

                if ($optimizeContent) {
                    $tmpStoreKey = 'thumb_' . $asset->getId() . '__' . md5($storagePath);
                    TmpStore::add($tmpStoreKey, $storagePath, 'image-optimize-queue');
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

    /**
     * @param Config $config
     * @param string $transformationType
     *
     * @return bool
     */
    private static function containsTransformationType(Config $config, string $transformationType): bool
    {
        $transformations = $config->getItems();
        if (is_array($transformations) && count($transformations) > 0) {
            foreach ($transformations as $transformation) {
                if (!empty($transformation)) {
                    if ($transformation['method'] == $transformationType) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
