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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;

class SnapshotQueryRunnerTest extends TestCase
{
    public function testMariaDbWrapsWithSetStatementInSeconds(): void
    {
        $this->assertSame(
            'SET STATEMENT max_statement_time=5 FOR SELECT COUNT(*) FROM objects',
            SnapshotQueryRunner::buildTimeoutSql('SELECT COUNT(*) FROM objects', true, 5)
        );
    }

    public function testMySqlInjectsMaxExecutionTimeHintInMilliseconds(): void
    {
        $this->assertSame(
            'SELECT /*+ MAX_EXECUTION_TIME(5000) */ COUNT(*) FROM objects',
            SnapshotQueryRunner::buildTimeoutSql('SELECT COUNT(*) FROM objects', false, 5)
        );
    }

    public function testMySqlHintIsInjectedOnlyOnTheOutermostSelect(): void
    {
        // Only the leading SELECT is bounded; the subquery SELECT must stay untouched.
        $this->assertSame(
            'SELECT /*+ MAX_EXECUTION_TIME(2000) */ MAX(c) FROM (SELECT COUNT(*) c FROM objects GROUP BY parentId) t',
            SnapshotQueryRunner::buildTimeoutSql(
                'SELECT MAX(c) FROM (SELECT COUNT(*) c FROM objects GROUP BY parentId) t',
                false,
                2
            )
        );
    }

    public function testNonSelectStatementIsPassedThroughUnchanged(): void
    {
        $this->assertSame('UPDATE t SET x = 1', SnapshotQueryRunner::buildTimeoutSql('UPDATE t SET x = 1', true, 5));
        $this->assertSame('UPDATE t SET x = 1', SnapshotQueryRunner::buildTimeoutSql('UPDATE t SET x = 1', false, 5));
    }

    public function testNonPositiveTimeoutDisablesTheCap(): void
    {
        $this->assertSame('SELECT 1', SnapshotQueryRunner::buildTimeoutSql('SELECT 1', true, 0));
        $this->assertSame('SELECT 1', SnapshotQueryRunner::buildTimeoutSql('SELECT 1', false, -3));
    }
}
