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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Cdn\Message\PurgeCdnTagMessage;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\ImageThumbnailConfigEvents;
use Pimcore\Event\Model\Asset\Image\Thumbnail\ConfigEvent as ImageThumbnailConfigEvent;
use Pimcore\Event\Model\Asset\Video\Thumbnail\ConfigEvent as VideoThumbnailConfigEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\VideoThumbnailConfigEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 *
 * Listens to asset and thumbnail-config lifecycle events and dispatches CDN purge messages:
 *  - Asset POST_UPDATE / POST_DELETE  → asset-{id}        (all thumbnail variants of that asset)
 *                                     + asset-path-{hash} (the original asset CDN entry)
 *  - Image/Video Thumbnail Config POST_UPDATE / POST_DELETE → thumb-{configName}
 *    (purges every CDN-cached thumbnail produced by that config across all assets)
 */
class CdnPurgeListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $cdnProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::POST_UPDATE => 'onAssetUpdate',
            AssetEvents::POST_DELETE => 'onAssetDelete',
            ImageThumbnailConfigEvents::POST_UPDATE => 'onImageThumbnailConfigChange',
            ImageThumbnailConfigEvents::POST_DELETE => 'onImageThumbnailConfigChange',
            VideoThumbnailConfigEvents::POST_UPDATE => 'onVideoThumbnailConfigChange',
            VideoThumbnailConfigEvents::POST_DELETE => 'onVideoThumbnailConfigChange',
        ];
    }

    public function onAssetUpdate(AssetEvent $event): void
    {
        if ($this->cdnProvider === '') {
            return;
        }

        $this->dispatchAssetPurge($event);
    }

    public function onAssetDelete(AssetEvent $event): void
    {
        if ($this->cdnProvider === '') {
            return;
        }

        $this->dispatchAssetPurge($event);
    }

    public function onImageThumbnailConfigChange(ImageThumbnailConfigEvent $event): void
    {
        if ($this->cdnProvider === '') {
            return;
        }

        $this->dispatchThumbConfigPurge($event->getConfig()->getName());
    }

    public function onVideoThumbnailConfigChange(VideoThumbnailConfigEvent $event): void
    {
        if ($this->cdnProvider === '') {
            return;
        }

        $this->dispatchThumbConfigPurge($event->getConfig()->getName());
    }

    private function dispatchAssetPurge(AssetEvent $event): void
    {
        $asset = $event->getAsset();

        // Purge all thumbnail variants for this asset.
        $this->bus->dispatch(new PurgeCdnTagMessage('asset-' . $asset->getId()));

        // Purge the original asset CDN entry using a path hash (no DB lookup needed — path
        // is available directly from the asset object, same string hashed on the response side).
        $assetWebPath = '/var/assets' . $asset->getFullPath();
        $pathHash = substr(hash('sha256', $assetWebPath), 0, 12);
        $this->bus->dispatch(new PurgeCdnTagMessage('asset-path-' . $pathHash));
    }

    private function dispatchThumbConfigPurge(string $configName): void
    {
        // Purges every CDN-cached image tagged with thumb-{configName}, regardless of asset.
        // The combined surrogate key asset-{id}-thumb-{configName} also expires via this tag because
        // CDN surrogate-key purges match any object that lists the tag in its Surrogate-Key header.
        $this->bus->dispatch(new PurgeCdnTagMessage('thumb-' . $configName));
    }
}
