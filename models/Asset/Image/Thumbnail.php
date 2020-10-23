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
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Thumbnail\ImageThumbnailTrait;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;

class Thumbnail
{
    use ImageThumbnailTrait;

    /**
     * @var bool[]
     */
    protected static $hasListenersCache = [];

    /**
     * @param Image $asset
     * @param string|array|Thumbnail\Config $config
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
     * @return string
     */
    public function getPath($deferredAllowed = true)
    {
        $fsPath = $this->getFileSystemPath($deferredAllowed);
        if ($this->getConfig()) {
            if ($this->useOriginalFile($this->asset->getFilename()) && $this->getConfig()->isSvgTargetFormatPossible()) {
                // we still generate the raster image, to get the final size of the thumbnail
                // we use getRealFullPath() here, to avoid double encoding (getFullPath() returns already encoded path)
                $fsPath = $this->asset->getRealFullPath();
            }
        }

        $path = $this->convertToWebPath($fsPath);

        if ($this->hasListeners(FrontendEvents::ASSET_IMAGE_THUMBNAIL)) {
            $event = new GenericEvent($this, [
                'filesystemPath' => $fsPath,
                'frontendPath' => $path,
            ]);
            \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_IMAGE_THUMBNAIL, $event);
            $path = $event->getArgument('frontendPath');
        }

        return $path;
    }

    /**
     * @param string $eventName
     *
     * @return bool
     */
    protected function hasListeners(string $eventName): bool
    {
        if (!isset(self::$hasListenersCache[$eventName])) {
            self::$hasListenersCache[$eventName] = \Pimcore::getEventDispatcher()->hasListeners($eventName);
        }

        return self::$hasListenersCache[$eventName];
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    protected function useOriginalFile($filename)
    {
        if ($this->getConfig()) {
            if (!$this->getConfig()->isRasterizeSVG() && preg_match("@\.svgz?$@", $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $deferredAllowed
     */
    public function generate($deferredAllowed = true)
    {
        $errorImage = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
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
                    $deferred = $deferredAllowed && $this->deferred;
                    $this->filesystemPath = Thumbnail\Processor::process($this->asset, $this->config, null, $deferred, true, $generated);
                } catch (\Exception $e) {
                    $this->filesystemPath = $errorImage;
                    Logger::error("Couldn't create thumbnail of image " . $this->asset->getRealFullPath());
                    Logger::error($e);
                }
            }
        }

        if ($this->hasListeners(AssetEvents::IMAGE_THUMBNAIL)) {
            \Pimcore::getEventDispatcher()->dispatch(AssetEvents::IMAGE_THUMBNAIL, new GenericEvent($this, [
                'deferred' => $deferred,
                'generated' => $generated,
            ]));
        }
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
     * @param string $path
     * @param array $options
     * @param Image $asset
     *
     * @return string
     */
    protected function addCacheBuster(string $path, array $options, Image $asset): string
    {
        if (isset($options['cacheBuster']) && $options['cacheBuster']) {
            $path = '/cache-buster-' . $asset->getModificationDate() . $path;
        }

        return $path;
    }

    /**
     * Get generated HTML for displaying the thumbnail image in a HTML document. (XHTML compatible).
     * Attributes can be added as a parameter. Attributes containing illegal characters are ignored.
     * Width and Height attribute can be overridden. SRC-attribute not.
     * Values of attributes are escaped.
     *
     * @param array $options Custom configurations and HTML attributes.
     * @param array $removeAttributes Listof key-value pairs of HTML attributes that should be removed
     *
     * @return string IMG-element with at least the attributes src, width, height, alt.
     */
    public function getHtml($options = [], $removeAttributes = [])
    {
        /** @var Image $image */
        $image = $this->getAsset();
        $attributes = [];
        $pictureAttribs = $options['pictureAttributes'] ?? []; // this is used for the html5 <picture> element

        // re-add support for disableWidthHeightAttributes
        if (isset($options['disableWidthHeightAttributes']) && $options['disableWidthHeightAttributes']) {
            // make sure the attributes are removed
            $removeAttributes = array_merge($removeAttributes, ['width', 'height']);
        } else {
            if ($this->getWidth()) {
                $attributes['width'] = $this->getWidth();
            }

            if ($this->getHeight()) {
                $attributes['height'] = $this->getHeight();
            }
        }

        $w3cImgAttributes = ['alt', 'align', 'border', 'height', 'hspace', 'ismap', 'longdesc', 'usemap',
            'vspace', 'width', 'class', 'dir', 'id', 'lang', 'style', 'title', 'xml:lang', 'onmouseover',
            'onabort', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseup',
            'onkeydown', 'onkeypress', 'onkeyup', 'itemprop', 'itemscope', 'itemtype', ];

        $customAttributes = [];
        if (isset($options['attributes']) && is_array($options['attributes'])) {
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
            } elseif (isset($options['defaultalt'])) {
                $altText = $options['defaultalt'];
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

            if (!(in_array($key, $w3cImgAttributes) || isset($customAttributes[$key]) || strpos($key, 'data-') === 0)) {
                continue;
            }

            //only include attributes with characters a-z and dashes in their name.
            if (preg_match('/^[a-z-]+$/i', $key)) {
                $attributes[$key] = $value;

                // some attributes need to be added also as data- attribute, this is specific to picturePolyfill
                if (in_array($key, ['alt'])) {
                    $pictureAttribs['data-' . $key] = $value;
                }
            }
        }

        $path = $this->getPath(true);
        $attributes['src'] = $this->addCacheBuster($path, $options, $image);

        $thumbConfig = $this->getConfig();

        if ($this->getConfig() && !$this->getConfig()->hasMedias() && !$this->useOriginalFile($path)) {
            // generate the srcset
            $srcSetValues = [];
            foreach ([1, 2] as $highRes) {
                $thumbConfigRes = clone $thumbConfig;
                $thumbConfigRes->setHighResolution($highRes);
                $srcsetEntry = $image->getThumbnail($thumbConfigRes, true) . ' ' . $highRes . 'x';
                $srcSetValues[] = $this->addCacheBuster($srcsetEntry, $options, $image);
            }
            $attributes['srcset'] = implode(', ', $srcSetValues);
        }

        foreach ($removeAttributes as $attribute) {
            unset($attributes[$attribute]);
            unset($pictureAttribs[$attribute]);
        }

        $isLowQualityPreview = false;
        if ((isset($options['lowQualityPlaceholder']) && $options['lowQualityPlaceholder']) && !Tool::isFrontendRequestByAdmin()) {
            $previewDataUri = $this->getAsset()->getLowQualityPreviewDataUri();
            if (!$previewDataUri) {
                // use a 1x1 transparent GIF as a fallback if no LQIP exists
                $previewDataUri = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            }

            $isLowQualityPreview = true;
            $attributes['data-src'] = $attributes['src'];
            if (isset($attributes['srcset'])) {
                $attributes['data-srcset'] = $attributes['srcset'];
            }
            $attributes['src'] = $previewDataUri;
            unset($attributes['srcset']);
        }

        // build html tag
        $htmlImgTag = '<img ' . array_to_html_attribute_string($attributes) . ' />';

        // $this->getConfig() can be empty, the original image is returned
        if ($this->getConfig() && ($this->getConfig()->hasMedias() || $this->getConfig()->getForcePictureTag())) {
            // output the <picture> - element
            $config = \Pimcore\Config::getSystemConfiguration('assets');
            $isWebPAutoSupport = $config['image']['thumbnails']['webp_auto_support'] ?? false;
            $isAutoFormat = ($isWebPAutoSupport && strtolower($this->getConfig()->getFormat()) === 'source') ? true : false;
            $webpSupportBackup = null;

            if ($isAutoFormat) {
                $webpSupportBackup = Image\Thumbnail\Processor::setHasWebpSupport(false);
                // ensure the default image is not WebP
                $this->filesystemPath = null;
                $path = $this->getPath(true);
            }

            $html = '<picture ' . array_to_html_attribute_string($pictureAttribs) . ' data-default-src="' . $this->addCacheBuster($path, $options, $image) . '">' . "\n";
            $mediaConfigs = $thumbConfig->getMedias();

            // currently only max-width is supported, the key of the media is WIDTHw (eg. 400w) according to the srcset specification
            ksort($mediaConfigs, SORT_NUMERIC);
            array_push($mediaConfigs, $thumbConfig->getItems()); //add the default config at the end - picturePolyfill v4

            foreach ($mediaConfigs as $mediaQuery => $config) {
                $srcSetValues = [];
                $sourceTagAttributes = [];
                $thumb = null;

                foreach ([1, 2] as $highRes) {
                    $thumbConfigRes = clone $thumbConfig;
                    $thumbConfigRes->selectMedia($mediaQuery);
                    $thumbConfigRes->setHighResolution($highRes);
                    $thumb = $image->getThumbnail($thumbConfigRes, true);
                    $srcSetValues[] = $this->addCacheBuster($thumb . ' ' . $highRes . 'x', $options, $image);

                    if ($this->useOriginalFile($this->asset->getFilename()) && $this->getConfig()->isSvgTargetFormatPossible()) {
                        break;
                    }

                    if ($isAutoFormat) {
                        $thumbConfigWebP = clone $thumbConfigRes;
                        $thumbConfigWebP->setFormat('webp');
                        $image->getThumbnail($thumbConfigWebP, true)->getPath();
                    }
                }

                if ($thumb) {
                    $sourceTagAttributes['srcset'] = implode(', ', $srcSetValues);
                    if ($mediaQuery) {
                        if (preg_match('/^[\d]+w$/', $mediaQuery)) {
                            // we replace the width indicator (400w) out of the name and build a proper media query for max width
                            $maxWidth = str_replace('w', '', $mediaQuery);
                            $sourceTagAttributes['media'] = '(max-width: ' . $maxWidth . 'px)';
                        } else {
                            // new style custom media queries
                            $sourceTagAttributes['media'] = $mediaQuery;
                        }

                        $thumb->reset();
                    }

                    if ($isLowQualityPreview) {
                        $sourceTagAttributes['data-srcset'] = $sourceTagAttributes['srcset'];
                        unset($sourceTagAttributes['srcset']);
                    }

                    $sourceTagAttributes['type'] = $thumb->getMimeType();

                    $sourceHtml = '<source ' . array_to_html_attribute_string($sourceTagAttributes) . ' />';
                    if ($isAutoFormat) {
                        $sourceHtmlWebP = preg_replace(['@(\.)(jpg|png)( \dx)@', '@(/)(jpeg|png)(")@'], '$1webp$3', $sourceHtml);
                        if ($sourceHtmlWebP != $sourceHtml) {
                            $html .= "\t" . $sourceHtmlWebP . "\n";
                        }
                    }

                    $html .= "\t" . $sourceHtml . "\n";
                }
            }

            $attrCleanedForPicture = $attributes;
            $attrCleanedForPicture['src'] = $this->addCacheBuster($path, $options, $image);
            unset($attrCleanedForPicture['width']);
            unset($attrCleanedForPicture['height']);

            if (isset($attrCleanedForPicture['srcset'])) {
                unset($attrCleanedForPicture['srcset']);
            }

            if ($isLowQualityPreview) {
                unset($attrCleanedForPicture['data-src']);
                unset($attrCleanedForPicture['data-srcset']);
                $attrCleanedForPicture['data-src'] = $attrCleanedForPicture['src'];
                $attrCleanedForPicture['src'] = $attributes['src'];
            }

            $htmlImgTagForpicture = "\t" . '<img ' . array_to_html_attribute_string($attrCleanedForPicture) .' />';

            $html .= $htmlImgTagForpicture . "\n";

            $html .= '</picture>' . "\n";

            $htmlImgTag = $html;

            if ($isAutoFormat) {
                Image\Thumbnail\Processor::setHasWebpSupport($webpSupportBackup);
            }
        }

        if (isset($options['useDataSrc']) && $options['useDataSrc']) {
            $htmlImgTag = preg_replace('/ src(set)?=/i', ' data-src$1=', $htmlImgTag);
        }

        return $htmlImgTag;
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

        if (isset($mediaConfigs[$name])) {
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
     * Get a thumbnail image configuration.
     *
     * @param string|array|Thumbnail\Config $selector Name, array or object describing a thumbnail configuration.
     *
     * @return Thumbnail\Config
     */
    protected function createConfig($selector)
    {
        return Thumbnail\Config::getByAutoDetect($selector);
    }
}
