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

namespace Pimcore\Tests\Unit\Asset;

use PHPUnit\Framework\TestCase;
use Pimcore\Config;
use Pimcore\Model\Asset;

/**
 * Checks the asset type mapping for font files, using the processed system configuration
 * (bundles/CoreBundle/config/pimcore/default.yaml normalized by the configuration tree).
 */
class FontTypeMappingTest extends TestCase
{
    public static function mappingProvider(): array
    {
        return [
            // fonts
            ['font/ttf', 'font.ttf', 'font'],
            ['font/sfnt', 'font.ttf', 'font'],
            ['font/otf', 'font.otf', 'font'],
            ['application/vnd.ms-opentype', 'font.otf', 'font'],
            ['application/x-font-ttf', 'font.ttf', 'font'],
            ['application/x-font-otf', 'font.otf', 'font'],
            ['font/collection', 'fonts.ttc', 'font'],
            ['font/woff', 'font.woff', 'font'],
            ['application/font-woff', 'font.woff', 'font'],
            ['font/woff2', 'font.woff2', 'font'],
            ['application/vnd.ms-fontobject', 'font.eot', 'font'],
            // MIME type couldn't be detected, fall back to the file extension
            ['application/octet-stream', 'font.ttf', 'font'],
            ['application/octet-stream', 'FONT.WOFF2', 'font'],
            // other types are not affected
            ['image/svg+xml', 'font.svg', 'image'],
            ['application/zip', 'fonts.zip', 'archive'],
            ['application/pdf', 'font-specimen.pdf', 'document'],
            ['text/plain', 'fonts.txt', 'text'],
            ['text/css', 'fonts.css', 'text'],
            ['application/octet-stream', 'font.bin', 'unknown'],
        ];
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testTypeFromMimeMapping(string $mimeType, string $filename, string $expectedType): void
    {
        $this->assertSame($expectedType, Asset::getTypeFromMimeMapping($mimeType, $filename));
    }

    public function testFontTypeIsMappedToFontClass(): void
    {
        $this->assertContains('font', Asset::getTypes());
        $this->assertSame(
            Asset\Font::class,
            ltrim(Config::getSystemConfiguration('assets')['type_definitions']['map']['font']['class'], '\\')
        );
    }

    public function testFontThumbnailConfigurationDefaults(): void
    {
        $thumbnails = Config::getSystemConfiguration('assets')['font']['thumbnails'];

        $this->assertTrue($thumbnails['enabled']);
        $this->assertSame(48, $thumbnails['font_size']);
        $this->assertStringStartsWith("The quick brown fox jumps over the lazy dog\n", $thumbnails['preview_text']);
        $this->assertStringEndsWith('$%()', $thumbnails['preview_text']);
    }
}
