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

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AutoconfigureTag('pimcore.cdn.image_transform_adapter', ['optimizer' => 'fastly'])]
class FastlyImageOptimizerAdapter implements ImageTransformAdapterInterface
{
    public function __construct(
        #[Autowire('%pimcore.cdn.base_url%')]
        private readonly string $baseUrl,
    ) {
    }

    public function buildUrl(string $originalPath, array $params): string
    {
        if ($this->baseUrl === '') {
            // Optimizer mode requires an absolute CDN host; refuse to emit a broken relative URL.
            throw new RuntimeException(
                'CDN_BASE_URL must be set when CDN_IMAGE_OPTIMIZER is enabled (FastlyImageOptimizerAdapter cannot build an absolute URL without it).'
            );
        }

        $query = $this->buildQuery($params);
        $path = $this->encodePath($originalPath);
        $url = rtrim($this->baseUrl, '/') . $path;

        return $query === '' ? $url : $url . '?' . $query;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildQuery(array $params): string
    {
        $parts = [];

        if (!empty($params['width'])) {
            $parts['width'] = (int) $params['width'];
        }
        if (!empty($params['height'])) {
            $parts['height'] = (int) $params['height'];
        }
        if (!empty($params['fit'])) {
            $parts['fit'] = (string) $params['fit'];
        }
        if (!empty($params['crop']) && is_array($params['crop'])) {
            $c = $params['crop'];
            // Fastly crop: width,height,x{n},y{n}
            $parts['crop'] = sprintf('%d,%d,x%d,y%d', $c['width'], $c['height'], $c['x'], $c['y']);
        }
        if (!empty($params['format'])) {
            if ($params['format'] === 'auto') {
                // Fastly content negotiation (Accept header) rather than a fixed format.
                $parts['auto'] = 'webp';
            } else {
                $parts['format'] = (string) $params['format'];
            }
        }
        if (!empty($params['quality'])) {
            $parts['quality'] = (int) $params['quality'];
        }
        if (!empty($params['dpr'])) {
            $parts['dpr'] = (int) $params['dpr'];
        }

        $pairs = [];
        foreach ($parts as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }

    /**
     * Percent-encode each path segment (filenames may contain spaces / non-ASCII), preserving the
     * slashes between segments — same rule as CdnPurgeListener::buildAssetUrlPath().
     */
    private function encodePath(string $originalPath): string
    {
        $segments = explode('/', ltrim($originalPath, '/'));
        $encoded = array_map('rawurlencode', $segments);

        return '/' . implode('/', $encoded);
    }
}
