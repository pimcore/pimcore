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
 * Translates a Pimcore image thumbnail Config into a provider-agnostic normalized parameter set,
 * or null when the config uses a transform we cannot faithfully reproduce on the CDN (caller then
 * falls back to Pimcore's own thumbnail generation).
 */
class ThumbnailTransformResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(Config $config): ?array
    {
        $params = [
            'width' => null,
            'height' => null,
            'fit' => null,
            'crop' => null,
            'format' => null,
            'quality' => null,
            'dpr' => null,
        ];

        foreach ($config->getItems() as $item) {
            if (empty($item) || !isset($item['method'])) {
                continue;
            }

            $args = $item['arguments'] ?? [];

            switch ($item['method']) {
                case 'resize':
                    $params['width'] = isset($args['width']) ? (int) $args['width'] : null;
                    $params['height'] = isset($args['height']) ? (int) $args['height'] : null;
                    $params['fit'] = null;

                    break;
                case 'scaleByWidth':
                    $params['width'] = isset($args['width']) ? (int) $args['width'] : null;

                    break;
                case 'scaleByHeight':
                    $params['height'] = isset($args['height']) ? (int) $args['height'] : null;

                    break;
                case 'contain':
                    $params['width'] = isset($args['width']) ? (int) $args['width'] : null;
                    $params['height'] = isset($args['height']) ? (int) $args['height'] : null;
                    $params['fit'] = 'bounds';

                    break;
                case 'cover':
                    $params['width'] = isset($args['width']) ? (int) $args['width'] : null;
                    $params['height'] = isset($args['height']) ? (int) $args['height'] : null;
                    $params['fit'] = 'cover';

                    break;
                case 'crop':
                    $params['crop'] = [
                        'x' => (int) ($args['x'] ?? 0),
                        'y' => (int) ($args['y'] ?? 0),
                        'width' => (int) ($args['width'] ?? 0),
                        'height' => (int) ($args['height'] ?? 0),
                    ];

                    break;
                default:
                    // Any transform outside the supported allowlist disqualifies CDN rewriting.
                    return null;
            }
        }

        $format = $config->getFormat();
        // Pimcore's "SOURCE"/empty format means "keep source / auto-negotiate"; map to Fastly auto.
        if ($format === '' || strtoupper($format) === 'SOURCE') {
            $params['format'] = 'auto';
        } else {
            $params['format'] = strtolower($format);
        }

        $quality = $config->getQuality();
        if ($quality > 0) {
            $params['quality'] = $quality;
        }

        $highRes = $config->getHighResolution();
        if ($highRes !== null && $highRes >= 2.0) {
            $params['dpr'] = 2;
        }

        return $params;
    }
}
