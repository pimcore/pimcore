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
use Pimcore\Tool\Frontend;

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
        'mirror' => ['mode'],
    ];

    /**
     * @var null|bool
     */
    protected static $hasWebpSupport = null;

    /**
     * @param string $format
     * @param array $allowed
     * @param string $fallback
     *
     * @return string
     */
    public static function getAllowedFormat($format, $allowed = [], $fallback = 'png')
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
     * @param string|null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @param bool $returnAbsolutePath
     * @param bool $generated
     *
     * @return mixed|string
     */
    public static function process(Asset $asset, Config $config, $fileSystemPath = null, $deferred = false, $returnAbsolutePath = false, &$generated = false)
    {
        $generated = false;
        $errorImage = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
        $format = strtolower($config->getFormat());
        // Optimize if allowed to strip info.
        $optimizeContent = (!$config->isPreserveColor() && !$config->isPreserveMetaData());
        $optimizedFormat = false;

        if (self::containsTransformationType($config, '1x1_pixel')) {
            return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        }

        if (!$fileSystemPath && $asset instanceof Asset) {
            $fileSystemPath = $asset->getFileSystemPath();
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
            } elseif ($format == 'tiff' && self::containsTransformationType($config, 'tifforiginal')) {
                return self::returnPath($fileSystemPath, $returnAbsolutePath);
            } elseif ($format == 'svg') {
                return $asset->getFullPath();
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

        if ($optimizedFormat && self::hasWebpSupport() && $image->supportsFormat('webp')) {
            $optimizedFormat = $optimizeContent = false;
            $format = 'webp';
        }

        $thumbDir = $asset->getImageThumbnailSavePath() . '/image-thumb__' . $asset->getId() . '__' . $config->getName();
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

        $fsPath = $thumbDir . '/' . $filename;

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in
        // \Pimcore\Bundle\CoreBundle\Controller\PublicServicesController::thumbnailAction()
        // so that it can be used also with dynamic configurations
        if ($deferred) {
            // only add the config to the TmpStore if necessary (e.g. if the config is auto-generated)
            if (!Config::exists($config->getName())) {
                $configId = 'thumb_' . $asset->getId() . '__' . md5(self::returnPath($fsPath, false));
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
        $image->setPreserveColor($config->isPreserveColor());
        $image->setPreserveMetaData($config->isPreserveMetaData());
        if (!$image->load($fileSystemPath, ['asset' => $asset])) {
            return self::returnPath($errorImage, $returnAbsolutePath);
        }

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

        $tmpFsPath = preg_replace('@\.([\w]+)$@', uniqid('.tmp-', true) . '.$1', $fsPath);
        $image->save($tmpFsPath, $format, $config->getQuality());
        @rename($tmpFsPath, $fsPath); // atomic rename to avoid race conditions

        $generated = true;

        if ($optimizeContent) {
            $filePath = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/', '', $fsPath);
            $tmpStoreKey = 'thumb_' . $asset->getId() . '__' . md5($filePath);
            TmpStore::add($tmpStoreKey, $filePath, 'image-optimize-queue');
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
     * @param Config $config
     * @param string $transformationType
     *
     * @return bool
     */
    protected static function containsTransformationType(Config $config, string $transformationType): bool
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

    /**
     * @param string $path
     * @param bool $absolute
     *
     * @return string
     */
    protected static function returnPath($path, $absolute)
    {
        if (!$absolute) {
            $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', '', $path);
        }

        return $path;
    }

    /**
     * @param bool|null $webpSupport
     *
     * @return bool|null
     */
    public static function setHasWebpSupport(?bool $webpSupport): ?bool
    {
        $prevValue = self::$hasWebpSupport;
        self::$hasWebpSupport = $webpSupport;

        return $prevValue;
    }

    /**
     * @return bool
     */
    protected static function hasWebpSupport(): bool
    {
        if (self::$hasWebpSupport !== null) {
            return self::$hasWebpSupport;
        }

        return Frontend::hasWebpSupport();
    }
}
