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

namespace Pimcore\Cdn;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @internal
 *
 * Single source of truth for the public web path under which Pimcore serves original assets. Owns
 * the URL prefix and the percent-encoding rule so the surrogate-key listener, the purge
 * listener/command, and the image-optimizer adapter all build the same paths and tags.
 *
 * The prefix follows `pimcore.assets.frontend_prefixes.source` — the prefix
 * Asset::getFrontendFullPath() prepends to every original-asset URL the system emits — and
 * falls back to {@see self::DEFAULT_PREFIX} when that is not configured (the documented
 * static-serving contract). Derived in PimcoreCoreExtension as `pimcore.cdn.original_asset_prefix`.
 */
final class AssetWebPath
{
    /**
     * Default public path prefix for originals, used when assets.frontend_prefixes.source
     * is not configured: the web server serves /var/assets/* directly off disk.
     */
    public const string DEFAULT_PREFIX = '/var/assets';

    public function __construct(
        #[Autowire('%pimcore.cdn.original_asset_prefix%')]
        private readonly string $prefix = self::DEFAULT_PREFIX,
    ) {
    }

    /**
     * Map an asset's full path (e.g. /folder/image.jpg) to its public web path
     * (e.g. /var/assets/folder/image.jpg). The result is unencoded.
     */
    public function forFullPath(string $assetFullPath): string
    {
        return $this->prefix . $assetFullPath;
    }

    /**
     * Whether a request path addresses an original asset under the configured prefix.
     * Response-side tagging and cacheability checks must use this so they stay in
     * sync with the purge-side paths built by {@see self::forFullPath()}.
     */
    public function isOriginalAssetPath(string $path): bool
    {
        return str_starts_with($path, $this->prefix . '/');
    }

    /**
     * Percent-encode the path, preserving slashes. Asset filenames may contain spaces or
     * non-ASCII characters; the CDN stores its cache key under the browser-encoded form, so
     * purge URLs and rewritten `<img src>` URLs must match.
     *
     * Delegates to urlencode_ignore_slash() — the same encoder Pimcore uses to emit public
     * asset URLs (Asset::getFrontendFullPath) — so the URLs this class builds can never
     * diverge from the URLs the site actually serves (including its `@2x` retina-suffix
     * exemption, which a plain per-segment rawurlencode would encode differently).
     */
    public function encode(string $webPath): string
    {
        return urlencode_ignore_slash($webPath);
    }
}
