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

interface ImageTransformAdapterInterface
{
    /**
     * Build a CDN image-transformation URL for the given original asset path and normalized
     * transform.
     *
     * @param string            $originalPath Relative origin path of the original asset
     *                                        (e.g. /var/assets/folder/image.jpg), unencoded.
     * @param ThumbnailTransform $transform   Normalized transform (see ThumbnailTransformResolver).
     *
     * @return string Absolute CDN URL ready for use in <img src>, or the original path unchanged
     *                when the adapter is a no-op.
     */
    public function buildUrl(string $originalPath, ThumbnailTransform $transform): string;
}
