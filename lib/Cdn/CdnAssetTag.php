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

/**
 * @internal
 *
 * Single source of truth for the CDN surrogate-key / cache-tag vocabulary. The tagging side
 * (CdnSurrogateKeyListener, which emits the Surrogate-Key/Cache-Tag headers) and every purge side
 * (CdnPurgeListener, CdnPurgeCommand) build their tags here, so a tag and the key that purges it
 * cannot drift apart — in particular {@see self::forPath()} pins the path-hash algorithm in one
 * place rather than being re-implemented at each call site.
 */
final class CdnAssetTag
{
    /** Tag covering every CDN-cached variant (thumbnails + original) of a single asset. */
    public function forAsset(int $assetId): string
    {
        return 'asset-' . $assetId;
    }

    /** Tag covering every CDN-cached thumbnail produced by a thumbnail config, across all assets. */
    public function forThumbConfig(string $configName): string
    {
        return 'thumb-' . $configName;
    }

    /** Tag covering one asset's thumbnails for one specific config. */
    public function forAssetThumb(int $assetId, string $configName): string
    {
        return 'asset-' . $assetId . '-thumb-' . $configName;
    }

    /**
     * Tag for the original-asset CDN entry, derived from its public web path (see {@see AssetWebPath}).
     * The hash makes the tag a fixed length regardless of path depth; both sides hash the identical
     * web-path string, so the values match.
     */
    public function forPath(string $webPath): string
    {
        return 'asset-path-' . hash('xxh3', $webPath);
    }
}
