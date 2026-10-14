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

namespace Pimcore\Tests\Unit\Models\Asset\Document;

use Pimcore\Model\Asset\Document\ImageThumbnail;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Document page thumbnails have no meaningful "source" image format, so a web-optimized
 * configuration is rendered as PNG - regardless of whether it is stored as "SOURCE" or "auto".
 */
final class ImageThumbnailAutoFormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function autoFormatProvider(): iterable
    {
        yield 'classic admin spelling' => ['SOURCE'];
        yield 'studio spelling' => ['auto'];
    }

    /**
     * @dataProvider autoFormatProvider
     */
    public function testAutoFormatIsRenderedAsPng(string $format): void
    {
        $config = new Config();
        $config->setName('documenttest_format_' . strtolower($format));
        $config->setFormat($format);

        $thumbnail = new ImageThumbnail(null, $config);

        $this->assertSame('PNG', $thumbnail->getConfig()->getFormat());
    }

    public function testExplicitFormatIsKept(): void
    {
        $config = new Config();
        $config->setName('documenttest_format_jpeg');
        $config->setFormat('JPEG');

        $thumbnail = new ImageThumbnail(null, $config);

        $this->assertSame('JPEG', $thumbnail->getConfig()->getFormat());
    }
}
