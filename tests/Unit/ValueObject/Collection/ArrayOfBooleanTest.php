<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\ValueObject\Collection;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\Collection\ArrayOfBoolean;
use ValueError;

/**
 * @internal
 */
final class ArrayOfBooleanTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonBooleanValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only boolean values. (integer given)');

        new ArrayOfBoolean([true, false, 1]);
    }

    public function testItShouldReturnValues(): void
    {
        $values = [true, false, true];
        $booleanArray = new ArrayOfBoolean($values);

        $this->assertSame($values, $booleanArray->getValue());
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $stringArray = new ArrayOfBoolean([true, false]);
        $serialized = serialize($stringArray);

        $serialized =  str_replace('i:42', 's:2:"42"', $serialized);
        $serialized = str_replace('b:1', 's:4:"true"', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only boolean values. (string given)');
        unserialize($serialized);
    }
}
