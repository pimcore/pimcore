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

namespace Pimcore\Telemetry\Snapshot;

use Closure;
use Exception;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Image\Thumbnail\Config\Listing as ImageListing;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config\Listing as VideoListing;
use function count;
use function in_array;
use function is_array;
use function strtoupper;
use function trim;

/**
 * The `thumbnails.*` namespace: how elaborate the installation's thumbnail set-up is - how many image and
 * video configurations exist, which output formats they target, how many transformations they chain -
 * default chain and media-query chains alike - and how many branch on media queries. The generated files
 * themselves are not counted: that is a walk over the thumbnail storage, not a configuration read.
 *
 * Both listings read the configuration store only (settings store or `var/config`), nothing is written.
 * They are independent probes: a listing that cannot be read leaves its own keys unknown and the other
 * one standing. Only Pimcore's own format labels are named (SOURCE, JPEG, WEBP, ...):
 * `allowed_formats` is project-configurable and a preset accepts any string, so any other label counts as
 * OTHER. Configuration names never leave.
 *
 * @internal
 */
final readonly class ThumbnailsCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    /**
     * The format labels Pimcore itself knows: the automatic format and the default `allowed_formats`.
     * `allowed_formats` is project-configurable and a preset accepts any string, so nothing outside this
     * list is ever named.
     */
    private const KNOWN_FORMATS = [
        'SOURCE', 'AVIF', 'EPS', 'GIF', 'JPEG', 'JPG', 'PJPEG', 'PNG', 'SVG', 'TIFF', 'WEBM', 'WEBP', 'PRINT',
    ];

    /**
     * Spellings of the automatic format: the classic admin UI's SOURCE, Studio's `auto`, legacy `original`.
     */
    private const SOURCE_ALIASES = ['SOURCE', 'AUTO', 'ORIGINAL'];

    private const FORMAT_LIMIT = 16;

    /**
     * @var Closure(): iterable<ImageConfig>
     */
    private Closure $loadImageConfigs;

    /**
     * @var Closure(): iterable<VideoConfig>
     */
    private Closure $loadVideoConfigs;

    /**
     * The loaders default to the two configuration listings; tests inject their own.
     *
     * @param (Closure(): iterable<ImageConfig>)|null $loadImageConfigs
     * @param (Closure(): iterable<VideoConfig>)|null $loadVideoConfigs
     */
    public function __construct(
        private CountMapInterface $countMap,
        ?Closure $loadImageConfigs = null,
        ?Closure $loadVideoConfigs = null,
    ) {
        $this->loadImageConfigs = $loadImageConfigs ?? static fn (): array => (new ImageListing())->load();
        $this->loadVideoConfigs = $loadVideoConfigs ?? static fn (): array => (new VideoListing())->load();
    }

    public function getNamespace(): string
    {
        return 'thumbnails';
    }

    public function collect(): array
    {
        $metrics = ['schema_version' => self::SCHEMA_VERSION];

        $images = $this->imageMetrics();
        if ($images !== null) {
            $metrics += $images;
        }

        $videos = $this->videoConfigCount();
        if ($videos !== null) {
            $metrics['video_config_count'] = $videos;
        }

        return $metrics;
    }

    /**
     * @return array<string, int|array<string, int>>|null
     */
    private function imageMetrics(): ?array
    {
        try {
            $configs = ($this->loadImageConfigs)();
        } catch (Exception) {
            return null;
        }

        $count = 0;
        $formats = [];
        $transformations = 0;
        $withMediaQueries = 0;

        foreach ($configs as $config) {
            $count++;
            $format = $this->formatKey($config->getFormat());
            $formats[$format] = ($formats[$format] ?? 0) + 1;
            $transformations += count($config->getItems());

            // a responsive preset carries one more chain per media query; those steps run just like the
            // default chain once the media query matches, so they count as transformations too
            foreach ($config->getMedias() as $items) {
                $transformations += is_array($items) ? count($items) : 0;
            }

            if ($config->getMedias() !== []) {
                $withMediaQueries++;
            }
        }

        return [
            'image_config_count' => $count,
            'image_format_breakdown' => $this->countMap->ranked($formats, self::FORMAT_LIMIT, 'OTHER'),
            'image_transformation_count' => $transformations,
            'image_configs_with_media_queries' => $withMediaQueries,
        ];
    }

    private function videoConfigCount(): ?int
    {
        try {
            $count = 0;
            foreach (($this->loadVideoConfigs)() as $ignored) {
                $count++;
            }

            return $count;
        } catch (Exception) {
            return null;
        }
    }

    private function formatKey(string $format): string
    {
        $label = strtoupper(trim($format));

        if (in_array($label, self::SOURCE_ALIASES, true)) {
            return 'SOURCE';
        }

        return in_array($label, self::KNOWN_FORMATS, true) ? $label : 'OTHER';
    }
}
