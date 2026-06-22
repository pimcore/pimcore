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
use Pimcore\Cdn\CdnCacheabilityResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 *
 * Emits Surrogate-Key and Cache-Tag response headers for thumbnail and original asset responses
 * so the CDN can cache and invalidate them by surrogate key.
 *
 * Only fires on 2xx responses — error responses are never tagged for CDN caching.
 *
 * Thumbnail tags: asset-{id}, thumb-{config}, asset-{id}-thumb-{config}
 *   → ID and config name are extracted from the URL pattern without any DB lookup.
 *
 * Original asset tags: asset-path-{hash}
 *   → {@see CdnAssetTag::forPath()} over the rawurldecoded request path (xxh3, 16 hex chars).
 *     The CdnPurgeListener derives the identical tag from $asset->getRealFullPath() via the
 *     same helper on the purge side.
 */
class CdnSurrogateKeyListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CdnCacheabilityResolver $cacheabilityResolver,
        private readonly CdnAssetTag $assetTag,
        private readonly AssetWebPath $assetWebPath = new AssetWebPath(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->cacheabilityResolver->isCdnCacheable($event->getRequest(), $event->getResponse())) {
            return;
        }

        // Symfony does not urldecode pathInfo; decode it so the asset-path-{hash} tag is
        // computed over the same decoded path string CdnPurgeListener/CdnPurgeCommand hash
        // (getRealFullPath()) — otherwise tags never match for filenames needing encoding.
        $path = rawurldecode($event->getRequest()->getPathInfo());
        $tags = $this->resolveTagsForPath($path);

        if (empty($tags)) {
            return;
        }

        $tagString = implode(' ', $tags);
        $response = $event->getResponse();
        $response->headers->set('Surrogate-Key', $tagString);
        $response->headers->set('Cache-Tag', $tagString);
    }

    /**
     * @return string[]
     */
    private function resolveTagsForPath(string $path): array
    {
        if (preg_match(CdnCacheabilityResolver::THUMBNAIL_PATTERN, $path, $matches)) {
            [, , $assetId, $configName] = $matches;
            $assetId = (int) $assetId;

            return [
                $this->assetTag->forAsset($assetId),
                $this->assetTag->forThumbConfig($configName),
                $this->assetTag->forAssetThumb($assetId, $configName),
            ];
        }

        if ($this->assetWebPath->isOriginalAssetPath($path)) {
            return [$this->assetTag->forPath($path)];
        }

        return [];
    }
}
