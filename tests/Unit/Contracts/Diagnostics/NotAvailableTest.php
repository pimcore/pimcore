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
use Pimcore\Contracts\Diagnostics\NotAvailable;
use Pimcore\Tests\Support\Test\TestCase;

class NotAvailableTest extends TestCase
{
    public function testRejectsAnEmptySubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('subject');
        new NotAvailable(subject: '', reason: 'Missing privilege.');
    }

    public function testRejectsAnEmptyReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reason');
        new NotAvailable(subject: 'innodb_status', reason: '');
    }

    public function testKeepsItsValues(): void
    {
        $entry = new NotAvailable(subject: 'innodb_status', reason: 'Missing privilege.');

        self::assertSame(['subject' => 'innodb_status', 'reason' => 'Missing privilege.'], $entry->toArray());
    }
}
