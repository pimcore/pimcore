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
use Pimcore\Contracts\Diagnostics\Finding;
use Pimcore\Contracts\Diagnostics\Severity;
use Pimcore\Tests\Support\Test\TestCase;

class FindingTest extends TestCase
{
    public function testRejectsAnEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('code');
        new Finding(code: '', severity: Severity::Warning, message: 'Queue is growing.');
    }

    public function testRejectsAnEmptyMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message');
        new Finding(code: 'queue_backlog', severity: Severity::Warning, message: '');
    }

    public function testRejectsAnEmptySubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('subject');
        new Finding(code: 'queue_backlog', severity: Severity::Warning, message: 'Queue is growing.', subject: '');
    }

    public function testKeepsItsValuesAndOmitsANullSubject(): void
    {
        $finding = new Finding(code: 'queue_backlog', severity: Severity::Warning, message: 'Queue is growing.');

        self::assertSame(
            ['code' => 'queue_backlog', 'severity' => Severity::Warning->value, 'message' => 'Queue is growing.'],
            $finding->toArray()
        );
    }
}
