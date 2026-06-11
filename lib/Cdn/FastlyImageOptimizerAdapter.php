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
        private readonly AssetWebPath $assetWebPath,
        #[Autowire('%pimcore.cdn.base_url%')]
        private readonly string $baseUrl,
    ) {
    }

    public function buildUrl(string $originalPath, ThumbnailTransform $transform): string
    {
        if ($this->baseUrl === '') {
            // Optimizer mode requires an absolute CDN host; refuse to emit a broken relative URL.
            throw new RuntimeException(
                'CDN_BASE_URL must be set when CDN_IMAGE_OPTIMIZER is enabled (FastlyImageOptimizerAdapter cannot build an absolute URL without it).'
            );
        }

        $query = $this->buildQuery($transform);
        $path = $this->assetWebPath->encode($originalPath);
        $url = rtrim($this->baseUrl, '/') . $path;

        return $query === '' ? $url : $url . '?' . $query;
    }

    private function buildQuery(ThumbnailTransform $transform): string
    {
        $parts = [];

        if ($transform->width !== null) {
            $parts['width'] = $transform->width;
        }
        if ($transform->height !== null) {
            $parts['height'] = $transform->height;
        }
        if ($transform->fit !== null && $transform->fit !== '') {
            $parts['fit'] = $transform->fit;
        }
        if ($transform->crop !== null) {
            $c = $transform->crop;
            // Fastly crop: width,height,x{n},y{n}
            $parts['crop'] = sprintf('%d,%d,x%d,y%d', $c->width, $c->height, $c->x, $c->y);
        }
        if ($transform->format !== null && $transform->format !== '') {
            if ($transform->format === 'auto') {
                // Fastly content negotiation (Accept header) rather than a fixed format.
                $parts['auto'] = 'webp';
            } else {
                $parts['format'] = $transform->format;
            }
        }
        if ($transform->quality !== null) {
            $parts['quality'] = $transform->quality;
        }
        if ($transform->dpr !== null) {
            $parts['dpr'] = $transform->dpr;
        }

        $pairs = [];
        foreach ($parts as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }
}
