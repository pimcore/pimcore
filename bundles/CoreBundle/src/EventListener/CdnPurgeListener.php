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

use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Cdn\Message\PurgeCdnAssetTreeMessage;
use Pimcore\Cdn\Message\PurgeCdnTagsMessage;
use Pimcore\Cdn\Message\PurgeCdnUrlMessage;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\ImageThumbnailConfigEvents;
use Pimcore\Event\Model\Asset\Image\Thumbnail\ConfigEvent as ImageThumbnailConfigEvent;
use Pimcore\Event\Model\Asset\Video\Thumbnail\ConfigEvent as VideoThumbnailConfigEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\VideoThumbnailConfigEvents;
use Pimcore\Model\Asset;
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
        private readonly CdnAssetTag $assetTag,
        private readonly AssetWebPath $assetWebPath,
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $cdnProvider,
        #[Autowire('%pimcore.cdn.base_url%')]
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

        // Version-only saves (editor autosave, "save only new version") leave the published
        // binary untouched — purging would evict valid CDN objects on every autosave.
        if ($event->hasArgument('saveVersionOnly') || $event->hasArgument('autoSave')) {
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

        // getRealFullPath() deliberately, not getFullPath(): during frontend requests
        // (e.g. a UGC upload controller saving an asset) getFullPath() returns the
        // urlencoded, frontend-prefixed path, which would hash to a tag the response
        // side never emitted and double-encode the purge URL below.
        $fullPath = $asset->getRealFullPath();

        $tags = [
            // All thumbnail variants of this asset.
            $this->assetTag->forAsset($asset->getId()),
            // The original asset CDN entry, via a path hash (no DB lookup needed — path
            // is available directly from the asset object, same string hashed on the response side).
            $this->assetTag->forPath($this->assetWebPath->forFullPath($fullPath)),
        ];

        $renamed = $oldPath !== null && $oldPath !== '' && $oldPath !== $fullPath;

        // If the asset was renamed/moved, also purge the previous path so its CDN-cached
        // response does not linger until natural TTL under the old URL.
        if ($renamed) {
            $tags[] = $this->assetTag->forPath($this->assetWebPath->forFullPath($oldPath));

            // Renaming/moving a folder repaths all descendants via a single SQL UPDATE
            // (Dao::updateChildPaths) with no per-child events — their CDN entries under
            // the old paths must be purged by walking the subtree asynchronously.
            if ($asset instanceof Asset\Folder) {
                $this->bus->dispatch(new PurgeCdnAssetTreeMessage($oldPath, $fullPath));
            }
        }

        // One message for all tags of this event: one transport insert in the editor's
        // save flow and one (chunked) provider request in the worker, instead of one each per tag.
        $this->bus->dispatch(new PurgeCdnTagsMessage($tags));

        // URL-based purges for original assets: nginx serves /var/assets/* directly off
        // disk so PHP never emits a Cache-Tag/Surrogate-Key for them, and tag-based
        // purge cannot reach them. When CDN_BASE_URL is configured, also issue an
        // absolute-URL purge against the public CDN host.
        if ($this->cdnBaseUrl !== '') {
            $base = rtrim($this->cdnBaseUrl, '/');
            $this->bus->dispatch(new PurgeCdnUrlMessage($base . $this->assetWebPath->encode($this->assetWebPath->forFullPath($fullPath))));

            if ($renamed) {
                $this->bus->dispatch(new PurgeCdnUrlMessage($base . $this->assetWebPath->encode($this->assetWebPath->forFullPath($oldPath))));
            }
        }
    }

    private function dispatchThumbConfigPurge(string $configName): void
    {
        // Purges every CDN-cached image tagged with thumb-{configName}, regardless of asset.
        // The combined surrogate key asset-{id}-thumb-{configName} also expires via this tag because
        // CDN surrogate-key purges match any object that lists the tag in its Surrogate-Key header.
        $this->bus->dispatch(new PurgeCdnTagsMessage([$this->assetTag->forThumbConfig($configName)]));
    }
}
