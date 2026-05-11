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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
 *   → SHA-256 (first 12 hex chars) of the request path. The CdnPurgeListener computes
 *     the identical hash from $asset->getFullPath() on the purge side.
 */
class CdnSurrogateKeyListener implements EventSubscriberInterface
{
    private const THUMBNAIL_PATTERN = '#(?:^|/)(image|video)-thumb__(\d+)__([^/]+)/#';

    private const ORIGINAL_ASSET_PATTERN = '#^/var/assets/#';

    public function __construct(
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $cdnProvider,
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

        // CDN disabled — nothing to emit.
        if ($this->cdnProvider === '') {
            return;
        }

        $response = $event->getResponse();

        // Never tag non-2xx responses — caching error responses at the CDN would serve
        // stale errors to all users until TTL expiry.
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        $tags = $this->resolveTagsForPath($path);

        if (empty($tags)) {
            return;
        }

        $tagString = implode(' ', $tags);

        $response->headers->set('Surrogate-Key', $tagString);
        $response->headers->set('Cache-Tag', $tagString);
    }

    /**
     * @return string[]
     */
    private function resolveTagsForPath(string $path): array
    {
        if (preg_match(self::THUMBNAIL_PATTERN, $path, $matches)) {
            [, , $assetId, $configName] = $matches;

            return [
                'asset-' . $assetId,
                'thumb-' . $configName,
                'asset-' . $assetId . '-thumb-' . $configName,
            ];
        }

        if (preg_match(self::ORIGINAL_ASSET_PATTERN, $path)) {
            $pathHash = substr(hash('sha256', $path), 0, 12);

            return ['asset-path-' . $pathHash];
        }

        return [];
    }
}
