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
use Pimcore\Model\Exception\NotFoundException;
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
     * @param string|array|Thumbnail\Config|null $config
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
            \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_IMAGE_THUMBNAIL);
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
            $event = new GenericEvent($this, [
                'deferred' => $deferred,
                'generated' => $generated,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::IMAGE_THUMBNAIL);
        }
    }

    /**
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
     * Get generated HTML for displaying the thumbnail image in a HTML document.
     *
     * @param array $options Custom configuration
     *
     * @return string
     */
    public function getHtml($options = [])
    {
        /** @var Image $image */
        $image = $this->getAsset();
        $thumbConfig = $this->getConfig();

        $pictureTagAttributes = $options['pictureAttributes'] ?? []; // this is used for the html5 <picture> element

        $previewDataUri = null;
        if ((isset($options['lowQualityPlaceholder']) && $options['lowQualityPlaceholder']) && !Tool::isFrontendRequestByAdmin()) {
            $previewDataUri = $image->getLowQualityPreviewDataUri();
            if (!$previewDataUri) {
                // use a 1x1 transparent GIF as a fallback if no LQIP exists
                $previewDataUri = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            }

            // this gets used in getImagTag() later
            $options['previewDataUri'] = $previewDataUri;
        }

        $isAutoFormat = $thumbConfig instanceof Image\Thumbnail\Config ? strtolower($thumbConfig->getFormat()) === 'source' : false;

        if ($isAutoFormat) {
            // ensure the default image is not WebP
            $this->filesystemPath = null;
        }

        $pictureCallback = $options['pictureCallback'] ?? null;
        if ($pictureCallback) {
            $pictureTagAttributes = $pictureCallback($pictureTagAttributes);
        }

        $html = '<picture ' . array_to_html_attribute_string($pictureTagAttributes) . '>' . "\n";

        if ($thumbConfig instanceof Image\Thumbnail\Config) {
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

                    $descriptor = $highRes . 'x';
                    $srcSetValues[] = $this->addCacheBuster($thumb . ' ' . $descriptor, $options, $image);

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
                        $sourceTagAttributes['media'] = $mediaQuery;
                        $thumb->reset();
                    }

                    if ($previewDataUri) {
                        $sourceTagAttributes['data-srcset'] = $sourceTagAttributes['srcset'];
                        unset($sourceTagAttributes['srcset']);
                    }

                    $sourceTagAttributes['type'] = $thumb->getMimeType();

                    $sourceCallback = $options['sourceCallback'] ?? null;
                    if ($sourceCallback) {
                        $sourceTagAttributes = $sourceCallback($sourceTagAttributes);
                    }

                    if (!empty($sourceTagAttributes)) {
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
            }
        }

        if (!($options['disableImgTag'] ?? null)) {
            $html .= "\t" . $this->getImageTag($options) . "\n";
        }

        $html .= '</picture>' . "\n";

        if (isset($options['useDataSrc']) && $options['useDataSrc']) {
            $html = preg_replace('/ src(set)?=/i', ' data-src$1=', $html);
        }

        return $html;
    }

    /**
     * @param array $options
     * @param array $removeAttributes
     * @return string
     */
    public function getImageTag(array $options = [], array $removeAttributes = []): string
    {
        /** @var Image $image */
        $image = $this->getAsset();
        $attributes = $options['imgAttributes'] ?? [];
        $callback = $options['imgCallback'] ?? null;

        if (isset($options['previewDataUri'])) {
            $attributes['src'] = $options['previewDataUri'];
        } else {
            $path = $this->getPath(true);
            $attributes['src'] = $this->addCacheBuster($path, $options, $image);
        }

        if ($this->getWidth()) {
            $attributes['width'] = $this->getWidth();
        }

        if ($this->getHeight()) {
            $attributes['height'] = $this->getHeight();
        }

        $altText = $attributes['alt'] ?? '';
        $titleText = $attributes['title'] ?? '';

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

        $attributes['alt'] = $altText;
        if (!empty($titleText)) {
            $attributes['title'] = $titleText;
        }

        $attributes['loading'] = 'lazy';

        foreach ($removeAttributes as $attribute) {
            unset($attributes[$attribute]);
        }

        if ($callback) {
            $attributes = $callback($attributes);
        }

        $htmlImgTag = '';
        if (!empty($attributes)) {
            $htmlImgTag = '<img ' . array_to_html_attribute_string($attributes) . ' />';
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
     *
     * @throws NotFoundException
     */
    protected function createConfig($selector)
    {
        $thumbnailConfig = Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && $thumbnailConfig === null) {
            throw new NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        return $thumbnailConfig;
    }
}
