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
use Pimcore\ValueObject\BooleanArray;

/**
 * @internal
 */
final class BooleanArrayTest extends TestCase
{
    public function testItShouldThrowExceptionWhenProvidedArrayContainsNonBooleanValues(): void
    {
        $this->expectException(InvalidValueObjectException::class);
        $this->expectExceptionMessage('Provided array must contain only boolean values. (integer given)');

        new BooleanArray([true, false, 1]);
    }


    public function testItShouldReturnValues(): void
    {
        $values = [true, false, true];
        $booleanArray = new BooleanArray($values);

        $this->assertSame($values, $booleanArray->getValue());
    }
}
