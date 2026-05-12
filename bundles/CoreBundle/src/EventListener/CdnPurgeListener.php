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
use Pimcore\Cdn\Message\PurgeCdnUrlMessage;
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
        #[Autowire('%env(CDN_BASE_URL)%')]
        private readonly string $cdnBaseUrl = '',
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

        // On rename/move, AssetEvent carries the previous path so we can purge its CDN entry too;
        // otherwise the stale asset-path-{hash} would linger until natural TTL.
        $oldPath = $event->hasArgument('oldPath') ? $event->getArgument('oldPath') : null;

        $this->dispatchAssetPurge($event, $oldPath);
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

    private function dispatchAssetPurge(AssetEvent $event, ?string $oldPath = null): void
    {
        $asset = $event->getAsset();

        // Purge all thumbnail variants for this asset.
        $this->bus->dispatch(new PurgeCdnTagMessage('asset-' . $asset->getId()));

        // Purge the original asset CDN entry using a path hash (no DB lookup needed — path
        // is available directly from the asset object, same string hashed on the response side).
        $assetWebPath = '/var/assets' . $asset->getFullPath();
        $pathHash = substr(hash('sha256', $assetWebPath), 0, 12);
        $this->bus->dispatch(new PurgeCdnTagMessage('asset-path-' . $pathHash));

        // If the asset was renamed/moved, also purge the previous path so its CDN-cached
        // response does not linger until natural TTL under the old URL.
        if ($oldPath !== null && $oldPath !== '' && $oldPath !== $asset->getFullPath()) {
            $oldPathHash = substr(hash('sha256', '/var/assets' . $oldPath), 0, 12);
            $this->bus->dispatch(new PurgeCdnTagMessage('asset-path-' . $oldPathHash));
        }

        // URL-based purges for original assets: nginx serves /var/assets/* directly off
        // disk so PHP never emits a Cache-Tag/Surrogate-Key for them, and tag-based
        // purge cannot reach them. When CDN_BASE_URL is configured, also issue an
        // absolute-URL purge against the public CDN host.
        if ($this->cdnBaseUrl !== '') {
            $base = rtrim($this->cdnBaseUrl, '/');
            $this->bus->dispatch(new PurgeCdnUrlMessage($base . '/var/assets' . $asset->getFullPath()));

            if ($oldPath !== null && $oldPath !== '' && $oldPath !== $asset->getFullPath()) {
                $this->bus->dispatch(new PurgeCdnUrlMessage($base . '/var/assets' . $oldPath));
            }
        }
    }

    private function dispatchThumbConfigPurge(string $configName): void
    {
        // Purges every CDN-cached image tagged with thumb-{configName}, regardless of asset.
        // The combined surrogate key asset-{id}-thumb-{configName} also expires via this tag because
        // CDN surrogate-key purges match any object that lists the tag in its Surrogate-Key header.
        $this->bus->dispatch(new PurgeCdnTagMessage('thumb-' . $configName));
    }
}
