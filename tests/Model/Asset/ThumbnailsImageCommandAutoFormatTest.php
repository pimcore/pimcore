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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Bundle\CoreBundle\Command\ThumbnailsImageCommand;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;

/**
 * pimcore:thumbnails:image pre-generates the WebP/AVIF variants of web-optimized thumbnail
 * configurations. This must work for both spellings of the format ("SOURCE" and "auto").
 */
final class ThumbnailsImageCommandAutoFormatTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $configNames = [];

    protected function tearDown(): void
    {
        foreach ($this->configNames as $name) {
            TestHelper::clearThumbnailConfiguration($name);
        }

        parent::tearDown();
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testAutoFormatIsExpandedToTheSameVariantsAsSourceFormat(): void
    {
        $sourceFormats = $this->fetchGeneratedFormats($this->createConfig('SOURCE'));
        $autoFormats = $this->fetchGeneratedFormats($this->createConfig('auto'));

        $this->assertContains('webp', $sourceFormats);
        $this->assertSame(
            str_replace('source', 'auto', $sourceFormats),
            $autoFormats
        );
    }

    /**
     * @return string[]
     */
    private function fetchGeneratedFormats(Config $config): array
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturn(false);

        $method = new ReflectionMethod(ThumbnailsImageCommand::class, 'fetchThumbnailConfigs');
        $configs = $method->invoke(new ThumbnailsImageCommand(), $input, $config->getName());

        return array_map(
            static fn (Config $config): string => strtolower($config->getFormat()),
            $configs
        );
    }

    private function createConfig(string $format): Config
    {
        $name = 'assettest_command_format_' . strtolower($format);
        TestHelper::clearThumbnailConfiguration($name);

        $config = new Config();
        $config->setName($name);
        $config->setFormat($format);
        $config->addItem('scaleByWidth', ['width' => 100], 'default');
        $config->save(true);
        $this->configNames[] = $name;

        return $config;
    }
}
