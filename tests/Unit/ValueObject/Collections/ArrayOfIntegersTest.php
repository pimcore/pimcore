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

namespace Pimcore\Tests\Unit\ValueObject\Collections;


use Pimcore\Exception\InvalidValueObjectException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\Collections\ArrayOfIntegers;

/**
 * @internal
 */
final class ArrayOfIntegersTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonIntegerValues(): void
    {
        $this->expectException(InvalidValueObjectException::class);
        $this->expectExceptionMessage('Provided array must contain only integer values. (string given)');

        new ArrayOfIntegers([1, 2, '3']);
    }

    public function testItShouldReturnValues(): void
    {
        $values = [1, 2, 3];
        $integerArray = new ArrayOfIntegers($values);

        $this->assertSame($values, $integerArray->getValue());
    }
}
