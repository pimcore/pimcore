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

namespace Pimcore\Tests\Unit\ValueObject\Collection;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\Collection\ArrayOfPositiveIntegers;
use ValueError;

/**
 * @internal
 */
final class ArrayOfPositiveIntegersTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedIntegerArrayIsNotPositive(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided integer must be positive. (-1 given)');

        new ArrayOfPositiveIntegers([-1]);
    }

    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonIntegerValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only integer values. (string given)');

        new ArrayOfPositiveIntegers([1, 2, '3']);
    }

    public function testItShouldReturnValues(): void
    {
        $values = [1, 2, 3];
        $positiveIntegerArray = new ArrayOfPositiveIntegers($values);

        $this->assertSame($values, $positiveIntegerArray->getValue());
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $array = new ArrayOfPositiveIntegers([1, 2, 42]);
        $serialized = serialize($array);

        $serialized =  str_replace('42', '-42', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided integer must be positive. (-42 given)');
        unserialize($serialized);
    }
}
