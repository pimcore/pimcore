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

namespace Pimcore\Tests\Unit\Model\Asset\Image\Thumbnail;

use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\VariantResolver;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
class VariantResolverTest extends TestCase
{
    private const MEDIA_QUERY = '(max-width: 600px)';

    public function testUntouchedConfigurationComesFirstAndIsNotRepeatedAsFactorOne(): void
    {
        $config = $this->createConfig();

        $variants = (new VariantResolver())->resolve($config, maxDpiFactor: 2);

        self::assertSame($config, $variants[0]);
        self::assertSame([null, 2.0], $this->describe($variants, 'getHighResolution'));
    }

    public function testConfiguredHighResolutionOfTheConfigurationIsKept(): void
    {
        $config = $this->createConfig();
        $config->setHighResolution(1.5);

        $variants = (new VariantResolver())->resolve($config, maxDpiFactor: 2);

        self::assertSame([1.5, 1.0, 2.0], $this->describe($variants, 'getHighResolution'));
    }

    public function testDpiFactorsFollowTheSrcsetLimit(): void
    {
        $variants = (new VariantResolver())->resolve($this->createConfig(), maxDpiFactor: 3);

        self::assertSame([null, 2.0, 3.0], $this->describe($variants, 'getHighResolution'));
    }

    public function testHighResolutionCanBeLeftOut(): void
    {
        $variants = (new VariantResolver())->resolve($this->createConfig(), includeHighResolution: false, maxDpiFactor: 3);

        self::assertCount(1, $variants);
    }

    public function testMediaQueriesAreExpandedForEveryDpiFactor(): void
    {
        $config = $this->createConfig(withMedia: true);

        $variants = (new VariantResolver())->resolve($config, maxDpiFactor: 2);

        $suffix = 'media--' . hash('crc32b', self::MEDIA_QUERY) . '--query';
        self::assertSame([null, null, $suffix, $suffix], $this->describe($variants, 'getFilenameSuffix'));
        self::assertSame([null, 2.0, 1.0, 2.0], $this->describe($variants, 'getHighResolution'));
        self::assertSame(200, $variants[2]->getItems()[0]['arguments']['width']);

        // the configuration handed in is not modified by selecting its media queries
        self::assertNull($config->getFilenameSuffix());
        self::assertSame(400, $config->getItems()[0]['arguments']['width']);
    }

    public function testMediaQueriesCanBeLeftOut(): void
    {
        $variants = (new VariantResolver())->resolve($this->createConfig(withMedia: true), includeMedias: false, maxDpiFactor: 2);

        self::assertSame([null, null], $this->describe($variants, 'getFilenameSuffix'));
    }

    public function testAutomaticFormatsAreExpandedAndCanBeExcluded(): void
    {
        $config = $this->createConfig();
        $config->setFormat('SOURCE');

        $expected = array_keys($config->getAutoFormatThumbnailConfigs());
        if (!$expected) {
            self::markTestSkipped('No automatic thumbnail format is supported by the image adapter in this environment.');
        }

        $resolver = new VariantResolver();

        $formats = $this->describe($resolver->resolve($config, includeHighResolution: false), 'getFormat');
        self::assertSame(['SOURCE', ...$expected], $formats);

        $formats = $this->describe(
            $resolver->resolve($config, includeHighResolution: false, excludedAutoFormats: $expected),
            'getFormat'
        );
        self::assertSame(['SOURCE'], $formats);
    }

    private function createConfig(bool $withMedia = false): Config
    {
        $config = new Config();
        $config->setName('unittest');
        $config->setFormat('PNG');
        $config->setItems([['method' => 'scaleByWidth', 'arguments' => ['width' => 400]]]);

        if ($withMedia) {
            $config->setMedias([
                self::MEDIA_QUERY => [['method' => 'scaleByWidth', 'arguments' => ['width' => 200]]],
            ]);
        }

        return $config;
    }

    /**
     * @param Config[] $variants
     */
    private function describe(array $variants, string $getter): array
    {
        return array_map(static fn (Config $variant) => $variant->$getter(), $variants);
    }
}
