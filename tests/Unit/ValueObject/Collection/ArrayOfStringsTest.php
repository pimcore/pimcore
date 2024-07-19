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
use Pimcore\ValueObject\Collection\ArrayOfStrings;
use ValueError;

/**
 * @internal
 */
final class ArrayOfStringsTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonStringValues(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only string values. (integer given)');

        new ArrayOfStrings(['1', '2', 3]);
    }

    public function testItShouldReturnValues(): void
    {
        $values = ['1', '2', '3'];
        $stringArray = new ArrayOfStrings($values);

        $this->assertSame($values, $stringArray->getValue());
    }

    public function testItShouldBeValidatedAfterUnSerialization(): void
    {
        $stringArray = new ArrayOfStrings(['1', '2', '42']);
        $serialized = serialize($stringArray);

        $serialized =  str_replace('s:2:"42"', 'i:42', $serialized);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Provided array must contain only string values. (integer given)');
        unserialize($serialized);
    }
}
