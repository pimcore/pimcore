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

namespace Pimcore\Model\Asset\Document;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\Factory as LockFactory;

class ImageThumbnail
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    /**
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
        $fsPath = $this->getFileSystemPath($deferredAllowed);
        $path = $this->convertToWebPath($fsPath);

        $event = new GenericEvent($this, [
            'filesystemPath' => $fsPath,
            'frontendPath' => $path,
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL, $event);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @param bool $deferredAllowed
     */
    public function generate($deferredAllowed = true)
    {
        $errorImage = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
        $generated = false;

        if (!$this->asset) {
            $this->filesystemPath = $errorImage;
        } elseif (!$this->filesystemPath) {
            $config = $this->getConfig();
            $config->setFilenameSuffix('page-' . $this->page);
            $path = null;
            $deferred = $deferredAllowed && $this->deferred;

            try {
                if (!$deferred) {
                    $converter = \Pimcore\Document::getInstance();
                    $converter->load($this->asset->getFileSystemPath());
                    $path = PIMCORE_TEMPORARY_DIRECTORY . '/document-image-cache/document_' . $this->asset->getId() . '__thumbnail_' .  $this->page . '.png';
                    if (!is_dir(dirname($path))) {
                        \Pimcore\File::mkdir(dirname($path));
                    }

                    if (!is_file($path)) {
                        $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock('document-thumbnail-' . $this->asset->getId() . '-' . $this->page);
                        if ($lock->acquire()) {
                            $converter->saveImage($path, $this->page);
                            $generated = true;
                            $lock->release();
                        } else {
                            $this->filesystemPath = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/please-wait.png';

                            return;
                        }
                    }
                }

                if ($config) {
                    $path = Image\Thumbnail\Processor::process($this->asset, $config, $path, $deferred, true, $generated);
                }

                $this->filesystemPath = $path;
            } catch (\Exception $e) {
                Logger::error("Couldn't create image-thumbnail of document " . $this->asset->getRealFullPath());
                Logger::error($e);
                $this->filesystemPath = $errorImage;
            }

            \Pimcore::getEventDispatcher()->dispatch(AssetEvents::DOCUMENT_IMAGE_THUMBNAIL, new GenericEvent($this, [
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
        if ($config) {
            $format = strtolower($config->getFormat());
            if ($format == 'source') {
                $config->setFormat('PNG');
            }
        }

        return $config;
    }
}
