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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;
use Pimcore\Telemetry\Snapshot\CountMap;
use Pimcore\Telemetry\Snapshot\ThumbnailsCollector;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use function array_fill;
use function json_encode;

class ThumbnailsCollectorTest extends TestCase
{
    private const IMAGE_KEYS = [
        'image_config_count',
        'image_format_breakdown',
        'image_transformation_count',
        'image_configs_with_media_queries',
    ];

    public function testNamespaceIsThumbnails(): void
    {
        $this->assertSame('thumbnails', $this->collector()->getNamespace());
    }

    public function testReportsCountsFormatsTransformationsAndMediaQueries(): void
    {
        $metrics = $this->collector(
            images: [
                $this->image('product_detail', 'JPEG', items: 2),
                $this->image(
                    'teaser',
                    'webp',
                    items: 3,
                    medias: ['(max-width: 480px)' => [['method' => 'scaleByWidth']]],
                ),
                $this->image('original', 'SOURCE'),
            ],
            videos: [new VideoConfig()],
        )->collect();

        $this->assertSame(1, $metrics['schema_version'] ?? null);
        $this->assertSame(3, $metrics['image_config_count'] ?? null);
        $this->assertSame(1, $metrics['video_config_count'] ?? null);
        $this->assertSame(['JPEG' => 1, 'SOURCE' => 1, 'WEBP' => 1], $metrics['image_format_breakdown'] ?? null);
        // 2 + 3 default steps and the one step of the media-query branch
        $this->assertSame(6, $metrics['image_transformation_count'] ?? null);
        $this->assertSame(1, $metrics['image_configs_with_media_queries'] ?? null);
    }

    /**
     * Only Pimcore's own format labels are named. `allowed_formats` is project-configurable and a preset
     * accepts any string, so a custom label is somebody's vocabulary and counts as OTHER; the aliases of
     * the automatic format (`auto`, `original`) read as SOURCE.
     */
    public function testOnlyPimcoreFormatLabelsAreNamed(): void
    {
        $metrics = $this->collector(images: [
            $this->image('a', 'SECRET'),
            $this->image('b', 'png'),
            $this->image('c', 'auto'),
            $this->image('d', 'original'),
        ])->collect();

        $this->assertSame(['SOURCE' => 2, 'OTHER' => 1, 'PNG' => 1], $metrics['image_format_breakdown'] ?? null);
        $this->assertStringNotContainsString('SECRET', (string) json_encode($metrics));
    }

    public function testNoImageConfigurationsIsAnHonestZero(): void
    {
        $metrics = $this->collector(images: [], videos: [])->collect();

        $this->assertSame(0, $metrics['image_config_count'] ?? null);
        $this->assertSame([], $metrics['image_format_breakdown'] ?? null);
        $this->assertSame(0, $metrics['image_transformation_count'] ?? null);
        $this->assertSame(0, $metrics['image_configs_with_media_queries'] ?? null);
        $this->assertSame(0, $metrics['video_config_count'] ?? null);
    }

    /**
     * The two listings are independent probes: one that cannot be read leaves its own keys unknown and
     * the other one standing.
     */
    public function testAFailingImageListingLeavesTheImageKeysUnknownButKeepsTheVideoCount(): void
    {
        $collector = new ThumbnailsCollector(
            new CountMap(),
            static fn (): array => throw new RuntimeException('configuration store unreachable'),
            static fn (): array => [new VideoConfig()],
        );
        $metrics = $collector->collect();

        $this->assertSame(1, $metrics['video_config_count'] ?? null);
        foreach (self::IMAGE_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }
    }

    public function testAFailingVideoListingLeavesOnlyTheVideoCountUnknown(): void
    {
        $collector = new ThumbnailsCollector(
            new CountMap(),
            static fn (): array => [],
            static fn (): array => throw new RuntimeException('configuration store unreachable'),
        );
        $metrics = $collector->collect();

        $this->assertArrayNotHasKey('video_config_count', $metrics);
        $this->assertSame(0, $metrics['image_config_count'] ?? null);
    }

    /**
     * Configuration names are the customer's vocabulary and never leave; only counts and format tokens do.
     */
    public function testNoConfigurationNameLeaks(): void
    {
        $metrics = $this->collector(images: [$this->image('secret_campaign_hero', 'PNG', items: 1)])->collect();

        $this->assertStringNotContainsString('secret', (string) json_encode($metrics));
    }

    /**
     * @param ImageConfig[] $images
     * @param VideoConfig[] $videos
     */
    private function collector(array $images = [], array $videos = []): ThumbnailsCollector
    {
        return new ThumbnailsCollector(new CountMap(), static fn (): array => $images, static fn (): array => $videos);
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $medias media query => transformations
     */
    private function image(string $name, string $format, int $items = 0, array $medias = []): ImageConfig
    {
        $config = new ImageConfig();
        $config->setName($name);
        $config->setFormat($format);
        $config->setItems(array_fill(0, $items, ['method' => 'scaleByWidth', 'arguments' => ['width' => 100]]));
        $config->setMedias($medias);

        return $config;
    }
}
