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
use Pimcore\Contracts\Diagnostics\CheckInterface;
use Pimcore\Contracts\Diagnostics\CheckResult;
use Pimcore\Contracts\Diagnostics\Finding;
use Pimcore\Contracts\Diagnostics\NotAvailable;
use Pimcore\Contracts\Diagnostics\Severity;
use Pimcore\Tests\Support\Test\TestCase;

class CheckResultTest extends TestCase
{
    public function testToArrayOmitsANullSubjectAndKeepsTheSeverityValue(): void
    {
        $result = new CheckResult(
            findings: [new Finding('slow', Severity::Warning, 'Fixed text.'), new Finding('x', Severity::Info, 'T', 'subj')],
            metrics: ['rows' => 3],
            notAvailable: [new NotAvailable('table', 'not_readable')],
        );
        self::assertSame([
            'findings' => [
                ['code' => 'slow', 'severity' => 'warning', 'message' => 'Fixed text.'],
                ['code' => 'x', 'severity' => 'info', 'message' => 'T', 'subject' => 'subj'],
            ],
            'metrics' => ['rows' => 3],
            'not_available' => [['subject' => 'table', 'reason' => 'not_readable']],
        ], $result->toArray());
    }

    public function testRejectsANonScalarMetric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('metrics');
        new CheckResult(metrics: ['nested' => ['a' => 1]]);
    }

    public function testRejectsANonStringMetricKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CheckResult(metrics: [0 => 1]);
    }

    public function testRejectsAFindingOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('findings');
        new CheckResult(findings: ['oops']);
    }

    public function testRejectsANotAvailableEntryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('notAvailable');
        new CheckResult(notAvailable: ['oops']);
    }

    public function testRejectsAnInfiniteMetric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('finite');
        new CheckResult(metrics: ['ratio' => INF]);
    }

    public function testRejectsANanMetric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('finite');
        new CheckResult(metrics: ['ratio' => NAN]);
    }

    public function testRejectsFindingsThatAreNotAList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"findings" must be a list');
        new CheckResult(findings: ['a' => new Finding('x', Severity::Info, 'T')]);
    }

    public function testRejectsNotAvailableEntriesThatAreNotAList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"notAvailable" must be a list');
        new CheckResult(notAvailable: [1 => new NotAvailable('table', 'not_readable')]);
    }

    public function testTheTagIsStable(): void
    {
        self::assertSame('pimcore.diagnostics.check', CheckInterface::TAG);
    }
}
