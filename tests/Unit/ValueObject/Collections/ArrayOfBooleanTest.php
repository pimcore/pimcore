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

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\ValueObject\Collections\ArrayOfBoolean;
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
}
