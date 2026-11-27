<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Asset\Font;

use Exception;
use Pimcore;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Exception\ThumbnailFormatNotSupportedException;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

/**
 * Image thumbnail of a font asset: a sample text is rendered with the font and the
 * resulting image is processed with the given image thumbnail configuration.
 *
 * @property Model\Asset\Font|null $asset
 */
final class ImageThumbnail implements ImageThumbnailInterface
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    public function __construct(
        ?Model\Asset\Font $asset,
        array|string|Image\Thumbnail\Config|null $config = null,
        bool $deferred = true
    ) {
        $this->asset = $asset;
        $this->config = $this->createConfig($config ?? []);
        $this->deferred = $deferred;
    }

    public function getPath(array $args = []): string
    {
        // set defaults
        $deferredAllowed = $args['deferredAllowed'] ?? true;
        $frontend = $args['frontend'] ?? \Pimcore\Tool::isFrontend();

        $pathReference = $this->getPathReference($deferredAllowed);

        $path = $this->convertToWebPath($pathReference, $frontend);

        $event = new GenericEvent($this, [
            'pathReference' => $pathReference,
            'frontendPath' => $path,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_FONT_IMAGE_THUMBNAIL);

        return $event->getArgument('frontendPath');
    }

    /**
     * @throws ThumbnailFormatNotSupportedException
     *
     * @internal
     */
    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && empty($this->pathReference)) {
            if (!$this->checkAllowedFormats($this->config->getFormat())) {
                throw new ThumbnailFormatNotSupportedException();
            }

            $config = $this->getConfig();
            // the suffix makes sure that thumbnails are regenerated when the preview settings change
            $config->setFilenameSuffix('font-' . $this->getPreviewHash());

            try {
                $cacheFileStream = null;
                if (!$deferred) {
                    if ($cacheFileStream = $this->getCacheFileStream()) {
                        $generated = true;
                    }
                }

                if ($deferred || $cacheFileStream) {
                    $this->pathReference = Image\Thumbnail\Processor::process($this->asset, $config, $cacheFileStream, $deferred, $generated);
                }
            } catch (Exception $e) {
                Logger::error("Couldn't create image-thumbnail of font " . $this->asset->getRealFullPath() . ': ' . $e);
            }
        }

        if (empty($this->pathReference)) {
            $this->pathReference = $this->getErrorPathReference();
        }

        $event = new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::FONT_IMAGE_THUMBNAIL);
    }

    /**
     * Renders the sample text of the font into a PNG file which is stored in the asset cache and
     * used as the source image for the thumbnail processor.
     *
     * @return resource|null
     */
    private function getCacheFileStream()
    {
        if (!$this->asset instanceof Model\Asset\Font) {
            return null;
        }

        $storage = Storage::get('asset_cache');
        $cacheFilePath = sprintf(
            '%s/%s/image-thumb__%s__font_original_image/preview_%s.png',
            rtrim($this->asset->getRealPath(), '/'),
            $this->asset->getId(),
            $this->asset->getId(),
            $this->getPreviewHash()
        );

        $imageAvailable = $storage->fileExists($cacheFilePath);
        if (!$imageAvailable) {
            $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
            $lock->acquire(true);

            try {
                // after we got the lock, check again if the image exists in the meantime - if not - generate it
                $imageAvailable = $storage->fileExists($cacheFilePath);
                if (!$imageAvailable) {
                    $tempFile = File::getLocalTempFilePath('png');

                    try {
                        $generator = new PreviewGenerator();
                        if (!$generator->generate(
                            $this->asset->getLocalFile(),
                            $tempFile,
                            $this->asset->getPreviewText(),
                            $this->asset->getPreviewFontSize()
                        )) {
                            Logger::info('Unable to render preview image of font ' . $this->asset->getRealFullPath());

                            return null;
                        }

                        $storage->write($cacheFilePath, (string) file_get_contents($tempFile));
                    } finally {
                        @unlink($tempFile);
                    }
                }
            } finally {
                $lock->release();
            }
        }

        return $storage->readStream($cacheFilePath);
    }

    private function getPreviewHash(): string
    {
        if (!$this->asset instanceof Model\Asset\Font) {
            return '';
        }

        return substr(md5($this->asset->getPreviewText() . '|' . $this->asset->getPreviewFontSize()), 0, 8);
    }

    /**
     * Get the public path to the thumbnail image.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    protected function createConfig(array|string|Image\Thumbnail\Config $selector): Image\Thumbnail\Config
    {
        $config = Image\Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && $config === null) {
            throw new NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        // the source of the thumbnail is a rendered PNG, keeping the "original" format of the font file isn't possible
        if ($config && (Config::isAutoFormat($config->getFormat()) || strtolower($config->getFormat()) === 'original')) {
            $config->setFormat('PNG');
        }

        return $config;
    }
}
