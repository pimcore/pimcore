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

namespace Pimcore\Tests\Unit\Models\Asset\Image\Thumbnail;

use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;

final class ConfigAutoFormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function autoFormatProvider(): iterable
    {
        yield 'classic admin spelling' => ['SOURCE'];
        yield 'classic admin spelling, lower case' => ['source'];
        yield 'studio spelling' => ['auto'];
        yield 'studio spelling, upper case' => ['AUTO'];
    }

    /**
     * @dataProvider autoFormatProvider
     */
    public function testRecognizesAutoFormatAliases(string $format): void
    {
        $this->assertTrue(Config::isAutoFormat($format));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function explicitFormatProvider(): iterable
    {
        yield 'original' => ['ORIGINAL'];
        yield 'png' => ['png'];
        yield 'webp' => ['webp'];
        yield 'print' => ['print'];
        yield 'empty' => [''];
    }

    /**
     * @dataProvider explicitFormatProvider
     */
    public function testDoesNotTreatExplicitFormatsAsAuto(string $format): void
    {
        $this->assertFalse(Config::isAutoFormat($format));
    }
}
