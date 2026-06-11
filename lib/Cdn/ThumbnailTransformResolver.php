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

use Pimcore\Model\Asset\Image\Thumbnail\Config;

/**
 * @internal
 *
 * Translates a Pimcore image thumbnail Config into a provider-agnostic, normalized
 * {@see ThumbnailTransform}, or null when the config uses a transform we cannot faithfully
 * reproduce on the CDN (caller then falls back to Pimcore's own thumbnail generation).
 */
class ThumbnailTransformResolver
{
    public function resolve(Config $config): ?ThumbnailTransform
    {
        $width = null;
        $height = null;
        $fit = null;
        $crop = null;

        foreach ($config->getItems() as $item) {
            if (empty($item) || !isset($item['method'])) {
                continue;
            }

            $args = $item['arguments'] ?? [];

            switch ($item['method']) {
                case 'resize':
                    $width = isset($args['width']) ? (int) $args['width'] : null;
                    $height = isset($args['height']) ? (int) $args['height'] : null;
                    $fit = null;

                    break;
                case 'scaleByWidth':
                    $width = isset($args['width']) ? (int) $args['width'] : null;

                    break;
                case 'scaleByHeight':
                    $height = isset($args['height']) ? (int) $args['height'] : null;

                    break;
                case 'contain':
                    $width = isset($args['width']) ? (int) $args['width'] : null;
                    $height = isset($args['height']) ? (int) $args['height'] : null;
                    $fit = 'bounds';

                    break;
                case 'cover':
                    // Pimcore's cover crop depends on `positioning` (topleft/topright/... or, when
                    // the asset has a focal point, a focal-point crop). Only the default `center`
                    // positioning maps cleanly to the CDN `cover` fit; anything else would crop
                    // differently, so disqualify CDN rewriting and let Pimcore generate it.
                    $positioning = $args['positioning'] ?? 'center';
                    if (!is_string($positioning) || strtolower($positioning) !== 'center') {
                        return null;
                    }

                    $width = isset($args['width']) ? (int) $args['width'] : null;
                    $height = isset($args['height']) ? (int) $args['height'] : null;
                    $fit = 'cover';

                    break;
                case 'crop':
                    $crop = new CropRegion(
                        (int) ($args['x'] ?? 0),
                        (int) ($args['y'] ?? 0),
                        (int) ($args['width'] ?? 0),
                        (int) ($args['height'] ?? 0),
                    );

                    break;
                default:
                    // Any transform outside the supported allowlist disqualifies CDN rewriting.
                    return null;
            }
        }

        $configFormat = $config->getFormat();
        // Pimcore's "SOURCE"/empty format means "keep source / auto-negotiate"; map to Fastly auto.
        $format = ($configFormat === '' || strtoupper($configFormat) === 'SOURCE')
            ? 'auto'
            : strtolower($configFormat);

        $configQuality = $config->getQuality();
        $quality = $configQuality > 0 ? $configQuality : null;

        // The transform only carries an integer 2x device-pixel-ratio. A 1x (or unset) factor is a
        // no-op (dpr omitted); exactly 2x maps to dpr=2. Any other factor (e.g. 1.5x, 3x, 4x) cannot
        // be reproduced faithfully here, so disqualify CDN rewriting rather than emit a wrong size.
        $highRes = $config->getHighResolution();
        $dpr = null;
        if ($highRes !== null && $highRes > 1.0) {
            if ($highRes === 2.0) {
                $dpr = 2;
            } else {
                return null;
            }
        }

        return new ThumbnailTransform(
            $width,
            $height,
            $fit,
            $crop,
            $format,
            $quality,
            $dpr,
        );
    }
}
