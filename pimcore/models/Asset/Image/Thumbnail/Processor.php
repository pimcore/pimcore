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
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;

class Processor
{
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
        'mirror' => ['mode']
    ];

    /**
     * @param $format
     * @param array $allowed
     * @param string $fallback
     *
     * @return string
     */
    public static function getAllowedFormat($format, $allowed = [], $fallback = 'png')
    {
        $typeMappings = [
            'jpg' => 'jpeg',
            'tif' => 'tiff'
        ];

        if (array_key_exists($format, $typeMappings)) {
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
     * @param $asset
     * @param Config $config
     * @param null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @param bool $returnAbsolutePath
     * @param bool $generated
     *
     * @return mixed|string
     */
    public static function process($asset, Config $config, $fileSystemPath = null, $deferred = false, $returnAbsolutePath = false, &$generated = false)
    {
        $generated = false;
        $errorImage = PIMCORE_WEB_ROOT . '/pimcore/static6/img/filetype-not-supported.png';
        $format = strtolower($config->getFormat());
        $contentOptimizedFormat = false;

        if (!$fileSystemPath && $asset instanceof Asset) {
            $fileSystemPath = $asset->getFileSystemPath();
        }

        if ($asset instanceof Asset) {
            $id = $asset->getId();
        } else {
            $id = 'dyn~' . crc32($fileSystemPath);
        }

        $fileExt = File::getFileExtension(basename($fileSystemPath));

        // simple detection for source type if SOURCE is selected
        if ($format == 'source' || empty($format)) {
            $format = self::getAllowedFormat($fileExt, ['jpeg', 'gif', 'png'], 'png');
            $contentOptimizedFormat = true; // format can change depending of the content (alpha-channel, ...)
        }

        if ($format == 'print') {
            $format = self::getAllowedFormat($fileExt, ['svg', 'jpeg', 'png', 'tiff'], 'png');

            if (($format == 'tiff' || $format == 'svg') && \Pimcore\Tool::isFrontentRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = 'png';
                $deferred = false; // deferred is default, but it's not possible when using isFrontentRequestByAdmin()
            } elseif ($format == 'tiff') {
                $transformations = $config->getItems();
                if (is_array($transformations) && count($transformations) > 0) {
                    foreach ($transformations as $transformation) {
                        if (!empty($transformation)) {
                            if ($transformation['method'] == 'tifforiginal') {
                                return self::returnPath($fileSystemPath, $returnAbsolutePath);
                            }
                        }
                    }
                }
            } elseif ($format == 'svg') {
                return self::returnPath($fileSystemPath, $returnAbsolutePath);
            }
        } elseif ($format == 'tiff') {
            if (\Pimcore\Tool::isFrontentRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = 'png';
                $deferred = false; // deferred is default, but it's not possible when using isFrontentRequestByAdmin()
            }
        }

        $thumbDir = $asset->getImageThumbnailSavePath() . '/thumb__' . $config->getName();
        $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename())) . '/', '', $asset->getFilename());
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
        }
        $filename .= '.' . $fileExtension;

        $fsPath = $thumbDir . '/' . $filename;

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in Pimcore\Controller\Plugin\Thumbnail::routeStartup()
        // so that it can be used also with dynamic configurations
        if ($deferred) {
            // only add the config to the TmpStore if necessary (the config is auto-generated)
            if (!Config::getByName($config->getName())) {
                $configId = 'thumb_' . $id . '__' . md5(self::returnPath($fsPath, false));
                TmpStore::add($configId, $config, 'thumbnail_deferred');
            }

            return self::returnPath($fsPath, $returnAbsolutePath);
        }

        // all checks on the file system should be below the deferred part for performance reasons (remote file systems)
        if (!file_exists($fileSystemPath)) {
            return self::returnPath($errorImage, $returnAbsolutePath);
        }

        if (!is_dir(dirname($fsPath))) {
            File::mkdir(dirname($fsPath));
        }

        $path = self::returnPath($fsPath, false);

        // check for existing and still valid thumbnail
        if (is_file($fsPath) and filemtime($fsPath) >= filemtime($fileSystemPath)) {
            return self::returnPath($fsPath, $returnAbsolutePath);
        }

        // transform image
        $image = Asset\Image::getImageTransformInstance();
        $image->setPreserveColor($config->isPreserveColor());
        $image->setPreserveMetaData($config->isPreserveMetaData());
        if (!$image->load($fileSystemPath)) {
            return self::returnPath($errorImage, $returnAbsolutePath);
        }

        $image->setUseContentOptimizedFormat($contentOptimizedFormat);

        $startTime = microtime(true);

        $transformations = $config->getItems();

        // check if the original image has an orientation exif flag
        // if so add a transformation at the beginning that rotates and/or mirrors the image
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($fileSystemPath);
            if (is_array($exif)) {
                if (array_key_exists('Orientation', $exif)) {
                    $orientation = intval($exif['Orientation']);

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
                                    'angle' => $angleMappings[$orientation]
                                ]
                            ]);
                        }

                        // values that have to be mirrored, this is not very common, but should be covered anyway
                        $mirrorMappings = [
                            2 => 'vertical',
                            4 => 'horizontal',
                            5 => 'vertical',
                            7 => 'horizontal'
                        ];

                        if (array_key_exists($orientation, $mirrorMappings)) {
                            array_unshift($transformations, [
                                'method' => 'mirror',
                                'arguments' => [
                                    'mode' => $mirrorMappings[$orientation]
                                ]
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
                    $mapping = self::$argumentMapping[$transformation['method']];

                    if (is_array($transformation['arguments'])) {
                        foreach ($transformation['arguments'] as $key => $value) {
                            $position = array_search($key, $mapping);
                            if ($position !== false) {

                                // high res calculations if enabled
                                if (!in_array($transformation['method'], ['cropPercent']) && in_array($key, ['width', 'height', 'x', 'y'])) {
                                    if ($highResFactor && $highResFactor > 1) {
                                        $value *= $highResFactor;
                                        $value = (int) ceil($value);

                                        if (!isset($transformation['arguments']['forceResize']) || !$transformation['arguments']['forceResize']) {
                                            // check if source image is big enough otherwise adjust the high-res factor
                                            if (in_array($key, ['width', 'x'])) {
                                                if ($sourceImageWidth < $value) {
                                                    $highResFactor = $calculateMaxFactor($highResFactor,
                                                        $sourceImageWidth, $value);
                                                    goto prepareTransformations;
                                                }
                                            } elseif (in_array($key, ['height', 'y'])) {
                                                if ($sourceImageHeight < $value) {
                                                    $highResFactor = $calculateMaxFactor($highResFactor,
                                                        $sourceImageHeight, $value);
                                                    goto prepareTransformations;
                                                }
                                            }
                                        }
                                    }
                                }

                                $arguments[$position] = $value;
                            }
                        }
                    }

                    ksort($arguments);
                    if (method_exists($image, $transformation['method'])) {
                        call_user_func_array([$image, $transformation['method']], $arguments);
                    }
                }
            }
        }

        $image->save($fsPath, $format, $config->getQuality());
        $generated = true;

        if ($contentOptimizedFormat) {
            $tmpStoreKey = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/', '', $fsPath);
            TmpStore::add($tmpStoreKey, '-', 'image-optimize-queue');
        }

        clearstatcache();

        Logger::debug('Thumbnail ' . $path . ' generated in ' . (microtime(true) - $startTime) . ' seconds');

        // set proper permissions
        @chmod($fsPath, File::getDefaultMode());

        // quick bugfix / workaround, it seems that imagemagick / image optimizers creates sometimes empty PNG chunks (total size 33 bytes)
        // no clue why it does so as this is not continuous reproducible, and this is the only fix we can do for now
        // if the file is corrupted the file will be created on the fly when requested by the browser (because it's deleted here)
        if (is_file($fsPath) && filesize($fsPath) < 50) {
            unlink($fsPath);
        }

        return self::returnPath($fsPath, $returnAbsolutePath);
    }

    /**
     * @param $path
     * @param $absolute
     *
     * @return mixed
     */
    protected static function returnPath($path, $absolute)
    {
        if (!$absolute) {
            $path = str_replace(PIMCORE_WEB_ROOT, '', $path);
        }

        return $path;
    }

    public static function processOptimizeQueue()
    {
        $ids = TmpStore::getIdsByTag('image-optimize-queue');

        // id = path of image relative to PIMCORE_TEMPORARY_DIRECTORY
        foreach ($ids as $id) {
            $file = PIMCORE_TEMPORARY_DIRECTORY . '/' . $id;
            if (file_exists($file)) {
                $originalFilesize = filesize($file);
                \Pimcore\Image\Optimizer::optimize($file);
                Logger::debug('Optimized image: ' . $file . ' saved ' . formatBytes($originalFilesize - filesize($file)));
            } else {
                Logger::debug('Skip optimizing of ' . $file . " because it doesn't exist anymore");
            }

            TmpStore::delete($id);
        }
    }
}
