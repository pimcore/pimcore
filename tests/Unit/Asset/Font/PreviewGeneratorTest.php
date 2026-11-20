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

namespace Pimcore\Tests\Unit\Asset\Font;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset\Font\PreviewGenerator;

/**
 * The font fixtures are a minimal, generated font (every printable ASCII character is a box glyph),
 * stored as TrueType and as WOFF.
 */
class PreviewGeneratorTest extends TestCase
{
    private const FIXTURE_DIR = __DIR__ . '/../../../Support/Resources/assets/font/';

    private ?string $targetFile = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!PreviewGenerator::isAvailable()) {
            $this->markTestSkipped('GD with FreeType support is required');
        }

        $this->targetFile = sys_get_temp_dir() . '/font-preview-' . bin2hex(random_bytes(8)) . '.png';
    }

    protected function tearDown(): void
    {
        if ($this->targetFile !== null) {
            @unlink($this->targetFile);
        }

        parent::tearDown();
    }

    public static function fontFileProvider(): array
    {
        return [
            'TrueType' => ['pimcore-test-box.ttf'],
            'WOFF' => ['pimcore-test-box.woff'],
        ];
    }

    /**
     * @dataProvider fontFileProvider
     */
    public function testRendersPreviewImage(string $fontFile): void
    {
        $generator = new PreviewGenerator();

        $this->assertTrue($generator->generate(self::FIXTURE_DIR . $fontFile, $this->targetFile, 'ABC abc', 40));

        $info = getimagesize($this->targetFile);
        $this->assertIsArray($info);
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThan($info[1], $info[0], 'a single line of text should result in a landscape image');

        // the box glyphs must have been drawn, the padding must stay blank
        $image = imagecreatefrompng($this->targetFile);
        $this->assertSame(0xFFFFFF, imagecolorat($image, 0, 0) & 0xFFFFFF);
        $this->assertTrue($this->containsDarkPixel($image), 'no glyphs have been rendered');
    }

    public function testEveryLineIncreasesTheHeight(): void
    {
        $generator = new PreviewGenerator();
        $fontFile = self::FIXTURE_DIR . 'pimcore-test-box.ttf';

        $this->assertTrue($generator->generate($fontFile, $this->targetFile, 'ABC', 40));
        $singleLine = getimagesize($this->targetFile);

        $this->assertTrue($generator->generate($fontFile, $this->targetFile, "ABC\r\nABC\nABC", 40));
        $threeLines = getimagesize($this->targetFile);

        $this->assertSame($singleLine[0], $threeLines[0]);
        $this->assertGreaterThan($singleLine[1] * 2, $threeLines[1]);
    }

    public function testFailsForFilesWhichAreNoFonts(): void
    {
        $generator = new PreviewGenerator();

        $this->assertFalse($generator->generate(__FILE__, $this->targetFile, 'ABC'));
        $this->assertFileDoesNotExist($this->targetFile);
    }

    public function testFailsForMissingFontFile(): void
    {
        $generator = new PreviewGenerator();

        $this->assertFalse($generator->generate(self::FIXTURE_DIR . 'does-not-exist.ttf', $this->targetFile, 'ABC'));
    }

    public function testFailsForEmptyText(): void
    {
        $generator = new PreviewGenerator();

        $this->assertFalse($generator->generate(self::FIXTURE_DIR . 'pimcore-test-box.ttf', $this->targetFile, " \n "));
    }

    private function containsDarkPixel(\GdImage $image): bool
    {
        for ($x = 0; $x < imagesx($image); $x += 2) {
            for ($y = 0; $y < imagesy($image); $y += 2) {
                if ((imagecolorat($image, $x, $y) & 0xFF) < 0x80) {
                    return true;
                }
            }
        }

        return false;
    }
}
