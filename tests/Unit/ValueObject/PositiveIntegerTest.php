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


namespace Pimcore\Tests\Unit\ValueObject;

use Pimcore\Exception\InvalidValueObjectException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\PositiveInteger;

/**
 * @internal
 */
final class PositiveIntegerTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedIntegerIsNotPositive(): void
    {
        $this->expectException(InvalidValueObjectException::class);
        $this->expectExceptionMessage('Provided integer must be positive. (-1 given)');

        new PositiveInteger(-1);
    }

    public function testItShouldReturnValue(): void
    {
        $value = 1;
        $positiveInteger = new PositiveInteger($value);

        $this->assertSame($value, $positiveInteger->getValue());
    }
}
