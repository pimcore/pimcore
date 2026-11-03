<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\DataObject\ClassificationstoreDataMarshaller\NumericRange;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The classification store persists every key into the generic `value`/`value2` columns, so a
 * NumericRange has to be folded into those two columns and restored from them.
 */
class NumericRangeTest extends TestCase
{
    public function testMarshalSplitsTheRangeIntoBothColumns(): void
    {
        $marshaller = new NumericRange();

        $this->assertSame(
            ['value' => 1.5, 'value2' => 42],
            $marshaller->marshal(['minimum' => 1.5, 'maximum' => 42])
        );
    }

    public function testMarshalKeepsAHalfOpenRange(): void
    {
        $marshaller = new NumericRange();

        $this->assertSame(
            ['value' => null, 'value2' => 7],
            $marshaller->marshal(['minimum' => null, 'maximum' => 7])
        );
    }

    public function testMarshalReturnsNullForANonArrayValue(): void
    {
        $marshaller = new NumericRange();

        $this->assertNull($marshaller->marshal(null));
    }

    /**
     * The columns are LONGTEXT, so the database hands the numbers back as strings while
     * NumericRange only accepts int|float|null.
     */
    public function testUnmarshalCastsTheStoredStringsBackToNumbers(): void
    {
        $marshaller = new NumericRange();

        $unmarshalled = $marshaller->unmarshal(['value' => '1.5', 'value2' => '42']);

        $this->assertSame(['minimum' => 1.5, 'maximum' => 42], $unmarshalled);
    }

    public function testUnmarshalKeepsAHalfOpenRange(): void
    {
        $marshaller = new NumericRange();

        $this->assertSame(
            ['minimum' => null, 'maximum' => 7],
            $marshaller->unmarshal(['value' => null, 'value2' => '7'])
        );
    }

    public function testUnmarshalReturnsNullForAnEmptyRange(): void
    {
        $marshaller = new NumericRange();

        $this->assertNull($marshaller->unmarshal(['value' => null, 'value2' => null]));
    }

    public function testRoundTrip(): void
    {
        $marshaller = new NumericRange();

        $marshalled = $marshaller->marshal(['minimum' => 0, 'maximum' => 10.25]);

        // the store writes into text columns, so read the values back as the database returns them
        $fromDatabase = [
            'value' => (string) $marshalled['value'],
            'value2' => (string) $marshalled['value2'],
        ];

        $this->assertSame(['minimum' => 0, 'maximum' => 10.25], $marshaller->unmarshal($fromDatabase));
    }
}
