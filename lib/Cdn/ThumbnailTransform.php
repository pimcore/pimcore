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
 * Provider-agnostic, normalized image-thumbnail transform produced by {@see ThumbnailTransformResolver}
 * and consumed by {@see ImageTransformAdapterInterface} implementations.
 *
 * A `null` property means "not set" and is omitted from the generated transform URL. `format` uses
 * the literal `'auto'` for source/auto-negotiated output (mapped per provider, e.g. Fastly `auto=webp`).
 */
final class ThumbnailTransform
{
    public function __construct(
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $fit = null,
        public readonly ?CropRegion $crop = null,
        public readonly ?string $format = null,
        public readonly ?int $quality = null,
        public readonly ?int $dpr = null,
    ) {
    }
}
