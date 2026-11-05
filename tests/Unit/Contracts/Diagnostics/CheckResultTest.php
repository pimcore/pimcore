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

    public function testTheTagIsStable(): void
    {
        self::assertSame('pimcore.diagnostics.check', CheckInterface::TAG);
    }
}
