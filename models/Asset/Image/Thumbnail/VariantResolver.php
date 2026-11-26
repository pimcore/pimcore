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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use function in_array;

/**
 * Expands a named thumbnail configuration into the variants a frontend can request for it:
 * the configuration as it is, its media queries, the srcset DPI factors and the automatic formats.
 *
 * Single owner of that expansion for everything that generates thumbnails ahead of the request
 * (`pimcore:thumbnails:image` and the asynchronous pre-generation), so they cannot drift apart.
 *
 * @internal
 */
final class VariantResolver
{
    /**
     * @param string[] $excludedAutoFormats automatic formats (e.g. `avif`, `webp`) to leave out
     * @param int|null $maxDpiFactor highest srcset DPI factor, defaults to `pimcore.assets.image.thumbnails.max_srcset_dpi_factor`
     *
     * @return Config[] one entry per physical output file, the untouched configuration first
     */
    public function resolve(
        Config $config,
        bool $includeMedias = true,
        bool $includeHighResolution = true,
        array $excludedAutoFormats = [],
        ?int $maxDpiFactor = null
    ): array {
        $variants = [];
        $this->add($variants, $config);

        $resolutions = [1];
        if ($includeHighResolution) {
            // the same factors Thumbnail::getSrcset() renders into the srcset attribute
            $maxDpiFactor = max(1, $maxDpiFactor ?? Config::getMaxDpiFactor());
            $resolutions = range(1, $maxDpiFactor);
        }

        $mediaNames = [null];
        if ($includeMedias) {
            foreach (array_keys($config->getMedias()) as $mediaName) {
                $mediaNames[] = (string) $mediaName;
            }
        }

        foreach ($mediaNames as $mediaName) {
            $mediaConfig = clone $config;
            if ($mediaName !== null) {
                $mediaConfig->selectMedia($mediaName);
            }

            foreach ($resolutions as $resolution) {
                $resolutionConfig = clone $mediaConfig;
                $resolutionConfig->setHighResolution($resolution);
                $this->add($variants, $resolutionConfig);

                if (!Config::isAutoFormat($resolutionConfig->getFormat())) {
                    continue;
                }

                foreach ($resolutionConfig->getAutoFormatThumbnailConfigs() as $autoFormat => $autoFormatConfig) {
                    if (!in_array($autoFormat, $excludedAutoFormats, true)) {
                        $this->add($variants, $autoFormatConfig);
                    }
                }
            }
        }

        return array_values($variants);
    }

    /**
     * Keeps the first of several variants that end up in the same file.
     *
     * @param array<string, Config> $variants
     */
    private function add(array &$variants, Config $variant): void
    {
        // a factor up to 1 neither scales the transformations nor adds the "@Nx" filename suffix
        $highResolution = (float) $variant->getHighResolution();
        if ($highResolution <= 1) {
            $highResolution = 1.0;
        }

        $key = $variant->getHash([
            'format' => strtolower($variant->getFormat()),
            'highResolution' => $highResolution,
            'filenameSuffix' => (string) $variant->getFilenameSuffix(),
        ]);

        $variants[$key] ??= $variant;
    }
}
