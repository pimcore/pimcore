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

namespace Pimcore\Tests\Unit\Contracts\Diagnostics;

use InvalidArgumentException;
use Pimcore\Contracts\Diagnostics\DataClass;
use Pimcore\Contracts\Diagnostics\DataContract;
use Pimcore\Tests\Support\Test\TestCase;

class DataContractTest extends TestCase
{
    public function testRejectsAReturnsEntryThatIsNotADataClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataContract(returns: ['metric']);
    }

    public function testRejectsAnEmptyReturnsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('returns');
        new DataContract(returns: []);
    }

    public function testRejectsAnEmptyNeverReturnsEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('neverReturns');
        new DataContract(returns: [DataClass::Metric], neverReturns: ['']);
    }

    public function testRejectsAnEmptySourcesEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sources');
        new DataContract(returns: [DataClass::Metric], sources: ['']);
    }

    public function testAcceptsACompleteContractAndKeepsItsValues(): void
    {
        $contract = new DataContract(returns: [DataClass::Metric], neverReturns: ['password'], sources: ['t'], doc: 'd');
        self::assertSame([DataClass::Metric], $contract->returns);
        self::assertSame('d', $contract->doc);
    }

    public function testThereIsNoDataClassForSecrets(): void
    {
        foreach (DataClass::cases() as $case) {
            self::assertStringNotContainsStringIgnoringCase('secret', $case->value);
            self::assertStringNotContainsStringIgnoringCase('credential', $case->value);
        }
    }
}
