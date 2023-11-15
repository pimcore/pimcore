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

namespace Pimcore\Model\Asset\Document;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

/**
 * @property Model\Asset\Document|null $asset
 */
final class ImageThumbnail implements ImageThumbnailInterface
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;
    use TemporaryFileHelperTrait;

    /**
     * @internal
     *
     */
    protected int $page = 1;

    public function __construct(?Model\Asset\Document $asset, array|string|Image\Thumbnail\Config $config = null, int $page = 1, bool $deferred = true)
    {
        $this->asset = $asset;
        $this->config = $this->createConfig($config ?? []);
        $this->page = $page;
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
        \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && empty($this->pathReference)) {
            $config = $this->getConfig();
            $cacheFileStream = null;
            $config->setFilenameSuffix('page-' . $this->page);

            try {
                if (!$deferred) {
                    if ($cacheFileStream = $this->getCacheFileStream()) {
                        $generated = true;
                    }
                }

                if ($config) {
                    if ($deferred || $cacheFileStream) {
                        $this->pathReference = Image\Thumbnail\Processor::process($this->asset, $config, $cacheFileStream, $deferred, $generated);
                    }
                }
            } catch (\Exception $e) {
                Logger::error("Couldn't create image-thumbnail of document " . $this->asset->getRealFullPath() . ': ' . $e);
            }
        }

        if (empty($this->pathReference)) {
            $this->pathReference = [
                'type' => 'error',
                'src' => '/bundles/pimcoreadmin/img/filetype-not-supported.svg',
            ];
        }

        $event = new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::DOCUMENT_IMAGE_THUMBNAIL);
    }

    /**
     * @return resource|null
     */
    private function getCacheFileStream()
    {
        $storage = Storage::get('asset_cache');
        $cacheFilePath = sprintf(
            '%s/%s/image-thumb__%s__document_original_image/page_%s.png',
            rtrim($this->asset->getRealPath(), '/'),
            $this->asset->getId(),
            $this->asset->getId(),
            $this->page
        );

        if (!$storage->fileExists($cacheFilePath)) {
            $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
            if ($lock->acquire()) {
                $tempFile = File::getLocalTempFilePath('png');

                try {
                    $converter = \Pimcore\Document::getInstance();
                    $converter->load($this->asset);
                    $converter->saveImage($tempFile, $this->page);
                    $storage->write($cacheFilePath, file_get_contents($tempFile));
                } finally {
                    $lock->release();
                }
            } else {
                Logger::info('Creation of cache file stream of document ' . $this->asset->getRealFullPath() . ' is locked');

                return null;
            }
        }

        return $storage->readStream($cacheFilePath);
    }

    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
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

        if ($config) {
            $format = strtolower($config->getFormat());
            if ($format == 'source') {
                $config->setFormat('PNG');
            }
        }

        return $config;
    }
}
