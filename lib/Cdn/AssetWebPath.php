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
 * Single source of truth for the public web path under which Pimcore serves original assets. Owns
 * the `/var/assets` prefix and the per-segment percent-encoding rule so the surrogate-key listener,
 * the purge listener/command, and the image-optimizer adapter all build the same paths and tags.
 */
final class AssetWebPath
{
    /** Public path prefix under which nginx serves original assets directly off disk. */
    public const PREFIX = '/var/assets';

    /**
     * Map an asset's full path (e.g. /folder/image.jpg) to its public web path
     * (e.g. /var/assets/folder/image.jpg). The result is unencoded.
     */
    public function forFullPath(string $assetFullPath): string
    {
        return self::PREFIX . $assetFullPath;
    }

    /**
     * Percent-encode each path segment, preserving the leading slash and the slashes between
     * segments. Asset filenames may contain spaces or non-ASCII characters; the CDN stores its
     * cache key under the browser-encoded form, so purge URLs and rewritten `<img src>` URLs must
     * match. rawurlencode() is idempotent on the safe character class, so already-clean segments
     * (including the `var`/`assets` prefix segments) pass through unchanged.
     */
    public function encode(string $webPath): string
    {
        $segments = explode('/', ltrim($webPath, '/'));

        return '/' . implode('/', array_map('rawurlencode', $segments));
    }
}
