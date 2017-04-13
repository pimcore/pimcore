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

namespace Pimcore\Model\Asset\Image;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\GenericEvent;

class Thumbnail
{
    /**
     * @var \Pimcore\Model\Asset\Image
     */
    protected $asset;

    /**
     * @var mixed|string
     */
    protected $filesystemPath;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $realWidth;

    /**
     * @var int
     */
    protected $realHeight;

    /**
     * @var string
     */
    protected $mimetype;

    /**
     * @var Thumbnail\Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $deferred = true;

    /**
     * @var bool
     */
    protected static $pictureElementInUse = false;

    /**
     * @var bool
     */
    protected static $embedPicturePolyfill = true;

    /**
     * @param $asset
     * @param null $config
     * @param bool $deferred
     */
    public function __construct($asset, $config = null, $deferred = true)
    {
        $this->asset = $asset;
        $this->deferred = $deferred;
        $this->config = $this->createConfig($config);
    }

    /**
     * @param bool $deferredAllowed
     *
     * @return mixed|string
     */
    public function getPath($deferredAllowed = true)
    {
        $fsPath = $this->getFileSystemPath($deferredAllowed);
        $path = str_replace(PIMCORE_WEB_ROOT, '', $fsPath);
        $path = urlencode_ignore_slash($path);

        $event = new GenericEvent($this, [
            'filesystemPath' => $fsPath,
            'frontendPath' => $path
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_IMAGE_THUMBNAIL, $event);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @param bool $deferredAllowed
     *
     * @return mixed|string
     */
    public function getFileSystemPath($deferredAllowed = false)
    {
        if (!$this->filesystemPath) {
            $this->generate($deferredAllowed);
        }

        return $this->filesystemPath;
    }

    /**
     * @param bool $deferredAllowed
     */
    public function generate($deferredAllowed = true)
    {
        $errorImage = PIMCORE_WEB_ROOT . '/pimcore/static6/img/filetype-not-supported.png';
        $deferred = false;
        $generated = false;

        if (!$this->asset) {
            $this->filesystemPath = $errorImage;
        } elseif (!$this->filesystemPath) {
            // if no correct thumbnail config is given use the original image as thumbnail
            if (!$this->config) {
                $this->filesystemPath = $this->asset->getRealFullPath();
            } else {
                try {
                    $deferred = ($deferredAllowed && $this->deferred) ? true : false;
                    $this->filesystemPath = Thumbnail\Processor::process($this->asset, $this->config, null, $deferred, true, $generated);
                } catch (\Exception $e) {
                    $this->filesystemPath = $errorImage;
                    Logger::error("Couldn't create thumbnail of image " . $this->asset->getRealFullPath());
                    Logger::error($e);
                }
            }
        }

        \Pimcore::getEventDispatcher()->dispatch(AssetEvents::IMAGE_THUMBNAIL, new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated
        ]));
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
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString()
    {
        return $this->getPath(true);
    }

    /**
     * @return int Width of the generated thumbnail image.
     */
    public function getWidth()
    {
        if (!$this->width) {
            $this->getDimensions();
        }

        return $this->width;
    }

    /**
     * Get the width of the generated thumbnail image in pixels.
     *
     * @return int Height of the generated thumbnail image.
     */
    public function getHeight()
    {
        if (!$this->height) {
            $this->getDimensions();
        }

        return $this->height;
    }

    /**
     * @return int real Width of the generated thumbnail image. (when using high resolution option)
     */
    public function getRealWidth()
    {
        if (!$this->realWidth) {
            $this->getDimensions();
        }

        return $this->realWidth;
    }

    /**
     * Get the real width of the generated thumbnail image in pixels. (when using high resolution option)
     *
     * @return int Height of the generated thumbnail image.
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
            if ($config) {
                $dimensions = $config->getEstimatedDimensions($asset);
            }

            if (empty($dimensions)) {
                // unable to calculate dimensions -> use fallback
                // generate the thumbnail and get dimensions from the thumbnail file
                $info = @getimagesize($this->getFileSystemPath());
                if ($info) {
                    $dimensions = [
                        'width' => $info[0],
                        'height' => $info[1]
                    ];
                }
            }

            $this->width = isset($dimensions['width']) ? $dimensions['width'] : null;
            $this->height = isset($dimensions['height']) ? $dimensions['height'] : null;

            // the following is only relevant if using high-res option (retina, ...)
            $this->realHeight = $this->height;
            $this->realWidth = $this->width;

            if ($config && $config->getHighResolution() && $config->getHighResolution() > 1) {
                $this->realWidth = floor($this->width * $config->getHighResolution());
                $this->realHeight = floor($this->height * $config->getHighResolution());
            }
        }

        return [
            'width' => $this->width,
            'height' => $this->height
        ];
    }

    /**
     * Get the height of the generated thumbnail image in pixels.
     *
     * @return string HTTP Mime Type of the generated thumbnail image.
     */
    public function getMimeType()
    {
        if (!$this->mimetype) {
            // get target mime type without actually generating the thumbnail (deferred)
            $mapping = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'pjpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'tiff' => 'image/tiff',
                'svg' => 'image/svg+xml',
            ];

            $targetFormat = strtolower($this->getConfig()->getFormat());
            $format = $targetFormat;
            $fileExt = \Pimcore\File::getFileExtension($this->getAsset()->getFilename());

            if ($targetFormat == 'source' || empty($targetFormat)) {
                $format = Thumbnail\Processor::getAllowedFormat($fileExt, ['jpeg', 'gif', 'png'], 'png');
            } elseif ($targetFormat == 'print') {
                $format = Thumbnail\Processor::getAllowedFormat($fileExt, ['svg', 'jpeg', 'png', 'tiff'], 'png');
                if (($format == 'tiff' || $format == 'svg') && \Pimcore\Tool::isFrontentRequestByAdmin()) {
                    // return a webformat in admin -> tiff cannot be displayed in browser
                    $format = 'png';
                }
            }

            if (array_key_exists($format, $mapping)) {
                $this->mimetype = $mapping[$format];
            } else {
                // unknown
                $this->mimetype = 'application/octet-stream';
            }
        }

        return $this->mimetype;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        $mapping = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/tiff' => 'tif',
            'image/svg+xml' => 'svg',
        ];

        $mimeType = $this->getMimeType();

        if (isset($mapping[$mimeType])) {
            return $mapping[$mimeType];
        }

        if ($this->getAsset()) {
            return \Pimcore\File::getFileExtension($this->getAsset()->getFilename());
        }

        return '';
    }

    /**
     * Get generated HTML for displaying the thumbnail image in a HTML document. (XHTML compatible).
     * Attributes can be added as a parameter. Attributes containing illigal characters are ignored.
     * Width and Height attribute can be overridden. SRC-attribute not.
     * Values of attributes are escaped.
     *
     * @param array $options Custom configurations and HTML attributes.
     * @param array $removeAttributes Listof key-value pairs of HTML attributes that should be removed
     *
     * @return string IMG-element with at least the attributes src, width, height, alt.
     */
    public function getHTML($options = [], $removeAttributes = [])
    {
        $image = $this->getAsset();
        $attributes = [];
        $pictureAttribs = []; // this is used for the html5 <picture> element

        // re-add support for disableWidthHeightAttributes
        if (isset($options['disableWidthHeightAttributes']) && $options['disableWidthHeightAttributes']) {
            // make sure the attributes are removed
            $removeAttributes = array_merge($removeAttributes, ['width', 'height']);
        } else {
            if ($this->getWidth()) {
                $attributes['width'] = 'width="'.$this->getWidth().'"';
            }

            if ($this->getHeight()) {
                $attributes['height'] = 'height="'.$this->getHeight().'"';
            }
        }

        $w3cImgAttributes = ['alt', 'align', 'border', 'height', 'hspace', 'ismap', 'longdesc', 'usemap',
            'vspace', 'width', 'class', 'dir', 'id', 'lang', 'style', 'title', 'xml:lang', 'onmouseover',
            'onabort', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseup',
            'onkeydown', 'onkeypress', 'onkeyup', 'itemprop', 'itemscope', 'itemtype'];

        $customAttributes = [];
        if (array_key_exists('attributes', $options) && is_array($options['attributes'])) {
            $customAttributes = $options['attributes'];
        }

        $altText = '';
        $titleText = '';
        if (isset($options['alt'])) {
            $altText = $options['alt'];
        }
        if (isset($options['title'])) {
            $titleText = $options['title'];
        }

        if (empty($titleText) && (!isset($options['disableAutoTitle']) || !$options['disableAutoTitle'])) {
            if ($image->getMetadata('title')) {
                $titleText = $image->getMetadata('title');
            }
        }

        if (empty($altText) && (!isset($options['disableAutoAlt']) || !$options['disableAutoAlt'])) {
            if ($image->getMetadata('alt')) {
                $altText = $image->getMetadata('alt');
            } else {
                $altText = $titleText;
            }
        }

        // get copyright from asset
        if ($image->getMetadata('copyright') && (!isset($options['disableAutoCopyright']) || !$options['disableAutoCopyright'])) {
            if (!empty($altText)) {
                $altText .= ' | ';
            }
            if (!empty($titleText)) {
                $titleText .= ' | ';
            }
            $altText .= ('© ' . $image->getMetadata('copyright'));
            $titleText .= ('© ' . $image->getMetadata('copyright'));
        }

        $options['alt'] = $altText;
        if (!empty($titleText)) {
            $options['title'] = $titleText;
        }

        $attributesRaw = array_merge($options, $customAttributes);

        foreach ($attributesRaw as $key => $value) {
            if (!(is_string($value) || is_numeric($value) || is_bool($value))) {
                continue;
            }

            if (!(in_array($key, $w3cImgAttributes) || array_key_exists($key, $customAttributes) || strpos($key, 'data-') === 0)) {
                continue;
            }

            //only include attributes with characters a-z and dashes in their name.
            if (preg_match('/^[a-z-]+$/i', $key)) {
                $attributes[$key] = $key . '="' . htmlspecialchars($value) . '"';

                // do not include all attributes
                if (!in_array($key, ['width', 'height', 'alt'])) {
                    $pictureAttribs[$key] = $key . '="' . htmlspecialchars($value) . '"';
                }

                // some attributes need to be added also as data- attribute, this is specific to picturePolyfill
                if (in_array($key, ['alt'])) {
                    $pictureAttribs['data-' . $key] = 'data-' . $key . '="' . htmlspecialchars($value) . '"';
                }
            }
        }

        $path = $this->getPath(true);
        $attributes['src'] = 'src="'. $path .'"';

        $thumbConfig = $this->getConfig();

        if ($this->getConfig() && !$this->getConfig()->hasMedias()) {
            // generate the srcset
            $srcSetValues = [];
            foreach ([1, 2] as $highRes) {
                $thumbConfigRes = clone $thumbConfig;
                $thumbConfigRes->setHighResolution($highRes);
                $srcsetEntry = $image->getThumbnail($thumbConfigRes, true) . ' ' . $highRes . 'x';
                $srcSetValues[] = $srcsetEntry;
            }
            $attributes['srcset'] = 'srcset="'. implode(', ', $srcSetValues) .'"';
        }

        foreach ($removeAttributes as $attribute) {
            unset($attributes[$attribute]);
            unset($pictureAttribs[$attribute]);
        }

        // build html tag
        $htmlImgTag = '<img '.implode(' ', $attributes).' />';

        // $this->getConfig() can be empty, the original image is returned
        if (!$this->getConfig() || !$this->getConfig()->hasMedias()) {
            return $htmlImgTag;
        } else {
            // output the <picture> - element

            // set this variable so that Pimcore_Controller_Plugin_Thumbnail::dispatchLoopShutdown() knows that
            // the picture polyfill script needs to be included
            self::$pictureElementInUse = true;

            // mobile first => fallback image is the smallest possible image
            $fallBackImageThumb = null;

            $html = '<picture ' . implode(' ', $pictureAttribs) . ' data-default-src="' . $path . '">' . "\n";
            $mediaConfigs = $thumbConfig->getMedias();

                // currently only max-width is supported, the key of the media is WIDTHw (eg. 400w) according to the srcset specification
                ksort($mediaConfigs, SORT_NUMERIC);
            array_push($mediaConfigs, $thumbConfig->getItems()); //add the default config at the end - picturePolyfill v4

                foreach ($mediaConfigs as $mediaQuery => $config) {
                    $srcSetValues = [];
                    foreach ([1, 2] as $highRes) {
                        $thumbConfigRes = clone $thumbConfig;
                        $thumbConfigRes->selectMedia($mediaQuery);
                        $thumbConfigRes->setHighResolution($highRes);
                        $thumb = $image->getThumbnail($thumbConfigRes, true);
                        $srcSetValues[] = $thumb . ' ' . $highRes . 'x';

                        if (!$fallBackImageThumb) {
                            $fallBackImageThumb = $thumb;
                        }
                    }

                    $html .= "\t" . '<source srcset="' . implode(', ', $srcSetValues) .'"';
                    if ($mediaQuery) {
                        // currently only max-width is supported, so we replace the width indicator (400w) out of the name
                        $maxWidth = str_replace('w', '', $mediaQuery);
                        $html .= ' media="(max-width: ' . $maxWidth . 'px)"';
                        $thumb->reset();
                    }
                    $html .= ' />' . "\n";
                }

                //$html .= "\t" . '<noscript>' . "\n\t\t" . $htmlImgTag . "\n\t" . '</noscript>' . "\n";

                $attrCleanedForPicture = $attributes;
            unset($attrCleanedForPicture['width']);
            unset($attrCleanedForPicture['height']);
            $attrCleanedForPicture['src'] = 'src="' . (string) $fallBackImageThumb . '"';
            $htmlImgTagForpicture = '<img '.implode(' ', $attrCleanedForPicture).' />';

            $html .= $htmlImgTagForpicture . "\n";

            $html .= '</picture>' . "\n";

            return $html;
        }
    }

    /**
     * @param string $name
     * @param int $highRes
     *
     * @return Thumbnail
     *
     * @throws \Exception
     */
    public function getMedia($name, $highRes = 1)
    {
        $thumbConfig = $this->getConfig();
        $mediaConfigs = $thumbConfig->getMedias();

        if (array_key_exists($name, $mediaConfigs)) {
            $thumbConfigRes = clone $thumbConfig;
            $thumbConfigRes->selectMedia($name);
            $thumbConfigRes->setHighResolution($highRes);
            $thumbConfigRes->setMedias([]);
            $thumb = $this->getAsset()->getThumbnail($thumbConfigRes);

            return $thumb;
        } else {
            throw new \Exception("Media query '" . $name . "' doesn't exist in thumbnail configuration: " . $thumbConfig->getName());
        }
    }

    /**
     * @return \Pimcore\Model\Asset\Image The original image from which this thumbnail is generated.
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Get thumbnail image configuration.
     *
     * @return Thumbnail\Config
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
            } elseif ($type = 'sha1') {
                return sha1_file($file);
            } else {
                throw new \Exception("hashing algorithm '" . $type . "' isn't supported");
            }
        }

        return null;
    }

    /**
     * Get a thumbnail image configuration.
     *
     * @param mixed $selector Name, array or object describing a thumbnail configuration.
     *
     * @return Thumbnail\Config
     */
    protected function createConfig($selector)
    {
        return Thumbnail\Config::getByAutoDetect($selector);
    }

    /**
     * @return bool
     */
    public static function isPictureElementInUse()
    {
        return self::$pictureElementInUse;
    }

    /**
     * Enables, when set to true, dispatchLoopShutdown of Pimcore_Controller_Plugin_Thumbnail
     *
     * @param bool $flag
     */
    public static function setPictureElementInUse($flag)
    {
        self::$pictureElementInUse = (bool) $flag;
    }

    /**
     * @return bool
     */
    public static function getEmbedPicturePolyfill()
    {
        return self::$embedPicturePolyfill;
    }

    /**
     * @param bool $embedPicturePolyfill
     */
    public static function setEmbedPicturePolyfill($embedPicturePolyfill)
    {
        self::$embedPicturePolyfill = $embedPicturePolyfill;
    }
}
