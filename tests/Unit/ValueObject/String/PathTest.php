<?php

declare(strict_types = 1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Unit\ValueObject\String;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\String\Path;
use ValueError;

/**
 * @internal
 */
final class PathTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedPathDoesNotStartWithSlash(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Path must start with a slash.');

        new Path('path');
    }

    public function testItShouldThrowExceptionWhenProvidedPathContainsConsecutiveSlashes(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Path must not contain consecutive slashes.');

        new Path('/path//path');
    }

    public function testItShouldReturnValue(): void
    {
        $value = '/path';
        $path = new Path($value);

        $this->assertSame($value, $path->getValue());
    }
}
