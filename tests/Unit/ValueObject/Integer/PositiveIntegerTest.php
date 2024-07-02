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

namespace Pimcore\Tests\Unit\ValueObject\Integer;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\Integer\PositiveInteger;
use ValueError;

/**
 * @internal
 */
final class PositiveIntegerTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedIntegerIsNotPositive(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided integer must be positive. (-1 given)');

        new PositiveInteger(-1);
    }

    public function testItShouldReturnValue(): void
    {
        $value = 1;
        $positiveInteger = new PositiveInteger($value);

        $this->assertSame($value, $positiveInteger->getValue());
    }

    public function testEquals(): void
    {
        $positiveInteger = new PositiveInteger(1);
        $positiveInteger2 = new PositiveInteger(1);
        $positiveInteger3 = new PositiveInteger(2);

        $this->assertTrue($positiveInteger->equals($positiveInteger2));
        $this->assertFalse($positiveInteger->equals($positiveInteger3));
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $positiveInteger = new PositiveInteger(42);
        $serialized = serialize($positiveInteger);

        $serialized = str_replace('42', '-42', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided integer must be positive. (-42 given)');
        unserialize($serialized);
    }
}
