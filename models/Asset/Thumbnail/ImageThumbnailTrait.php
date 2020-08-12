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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Thumbnail;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;

trait ImageThumbnailTrait
{
    /**
     * @var Asset
     */
    protected $asset;

    /**
     * @var Image\Thumbnail\Config
     */
    protected $config;

    /**
     * @var string|null
     */
    protected $filesystemPath;

    /**
     * @var int|null
     */
    protected $width;

    /**
     * @var int|null
     */
    protected $height;

    /**
     * @var int|null
     */
    protected $realWidth;

    /**
     * @var int|null
     */
    protected $realHeight;

    /**
     * @var string
     */
    protected $mimetype;

    /**
     * @var bool
     */
    protected $deferred = true;

    /**
     * @param bool $deferredAllowed
     *
     * @return string
     */
    public function getFileSystemPath($deferredAllowed = false)
    {
        if (!$this->filesystemPath) {
            $this->generate($deferredAllowed);
        }

        return $this->filesystemPath;
    }

    public function reset()
    {
        $this->filesystemPath = null;
        $this->width = null;
        $this->height = null;
        $this->realHeight = null;
        $this->realWidth = null;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        if (!$this->width) {
            $this->getDimensions();
        }

        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        if (!$this->height) {
            $this->getDimensions();
        }

        return $this->height;
    }

    /**
     * @return int
     */
    public function getRealWidth()
    {
        if (!$this->realWidth) {
            $this->getDimensions();
        }

        return $this->realWidth;
    }

    /**
     * @return int
     */
    public function getRealHeight()
    {
        if (!$this->realHeight) {
            $this->getDimensions();
        }

        return $this->realHeight;
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        if (!$this->width || !$this->height) {
            $config = $this->getConfig();
            $asset = $this->getAsset();
            $dimensions = [];

            // first we try to calculate the final dimensions based on the thumbnail configuration
            if ($config && $asset instanceof Image) {
                $dimensions = $config->getEstimatedDimensions($asset);
            }

            if (empty($dimensions)) {
                // unable to calculate dimensions -> use fallback
                // generate the thumbnail and get dimensions from the thumbnail file
                $info = @getimagesize($this->getFileSystemPath());
                if ($info) {
                    $dimensions = [
                        'width' => $info[0],
                        'height' => $info[1],
                    ];
                }
            }

            $this->width = isset($dimensions['width']) ? $dimensions['width'] : null;
            $this->height = isset($dimensions['height']) ? $dimensions['height'] : null;

            // the following is only relevant if using high-res option (retina, ...)
            $this->realHeight = $this->height;
            $this->realWidth = $this->width;

            if ($config && $config->getHighResolution() && $config->getHighResolution() > 1) {
                $this->realWidth = (int)floor($this->width * $config->getHighResolution());
                $this->realHeight = (int)floor($this->height * $config->getHighResolution());
            }
        }

        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @return Image\Thumbnail\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $type
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getChecksum($type = 'md5')
    {
        $file = $this->getFileSystemPath();
        if (is_file($file)) {
            if ($type == 'md5') {
                return md5_file($file);
            } elseif ($type == 'sha1') {
                return sha1_file($file);
            } else {
                throw new \Exception("hashing algorithm '" . $type . "' isn't supported");
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        if (!$this->mimetype) {
            $filesystemPath = $this->getFileSystemPath(true);
            if (strpos($filesystemPath, 'data:image/') === 0) {
                $this->mimetype = substr($filesystemPath, 5, strpos($filesystemPath, ';') - 5);
            } else {
                $fileExt = $this->getFileExtension();
                $mapping = \Pimcore::getContainer()->getParameter('pimcore.mime.extensions');

                if (isset($mapping[$fileExt])) {
                    $this->mimetype = $mapping[$fileExt];
                } else {
                    // unknown
                    $this->mimetype = 'application/octet-stream';
                }
            }
        }

        return $this->mimetype;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return \Pimcore\File::getFileExtension($this->getPath(true));
    }

    /**
     * @param string $filesystemPath
     *
     * @return string
     */
    protected function convertToWebPath(string $filesystemPath): string
    {
        if (strpos($filesystemPath, 'data:image/') === 0) {
            // do not convert base64 encoded images
            return $filesystemPath;
        }

        $path = preg_replace([
            '@^' . preg_quote(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', '@') . '@',
            '@^' . preg_quote(PIMCORE_WEB_ROOT, '@') . '@',
        ], '', $filesystemPath);

        $path = urlencode_ignore_slash($path);

        return $path;
    }
}
