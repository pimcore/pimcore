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

final class ImageThumbnail
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;
    use TemporaryFileHelperTrait;

    /**
     * @internal
     *
     * @var int
     */
    protected $page = 1;

    /**
     * @param Model\Asset\Document $asset
     * @param string|array|Image\Thumbnail\Config $config
     * @param int $page
     * @param bool $deferred
     */
    public function __construct($asset, $config = null, $page = 1, $deferred = true)
    {
        $this->asset = $asset;
        $this->config = $this->createConfig($config);
        $this->page = $page;
        $this->deferred = $deferred;
    }

    /**
     * @param bool $deferredAllowed
     *
     * @return string
     */
    public function getPath($deferredAllowed = true)
    {
        $pathReference = $this->getPathReference($deferredAllowed);
        $path = $this->convertToWebPath($pathReference);

        $event = new GenericEvent($this, [
            'pathReference' => $pathReference,
            'frontendPath' => $path,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @param bool $deferredAllowed
     */
    public function generate($deferredAllowed = true)
    {
        $generated = false;

        if ($this->asset && empty($this->pathReference)) {
            $config = $this->getConfig();
            $cacheFileStream = null;
            $config->setFilenameSuffix('page-' . $this->page);
            $deferred = $deferredAllowed && $this->deferred;

            try {
                if (!$deferred) {
                    $storage = Storage::get('asset_cache');
                    $cacheFilePath = sprintf('%s/image-thumb__%s__document_original_image/page_%s.png',
                        rtrim($this->asset->getRealPath(), '/'),
                        $this->asset->getId(),
                        $this->page
                    );

                    if (!$storage->fileExists($cacheFilePath)) {
                        $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
                        $converter = \Pimcore\Document::getInstance();
                        $converter->load($this->asset);
                        if ($lock->acquire()) {
                            $tempFile = File::getLocalTempFilePath('png');
                            $converter->saveImage($tempFile, $this->page);
                            $generated = true;
                            $lock->release();
                            $storage->write($cacheFilePath, file_get_contents($tempFile));
                            unlink($tempFile);
                        }
                    }

                    $cacheFileStream = $storage->readStream($cacheFilePath);
                }

                if ($config) {
                    $this->pathReference = Image\Thumbnail\Processor::process($this->asset, $config, $cacheFileStream, $deferred, $generated);
                }
            } catch (\Exception $e) {
                Logger::error("Couldn't create image-thumbnail of document " . $this->asset->getRealFullPath());
                Logger::error($e);
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
        return $this->getPath();
    }

    /**
     * @param string|array|Image\Thumbnail\Config $selector
     *
     * @return Image\Thumbnail\Config
     */
    protected function createConfig($selector)
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
