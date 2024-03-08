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
