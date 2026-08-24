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

use Doctrine\DBAL\Connection;
use Pimcore\Telemetry\Snapshot\SnapshotBuilder;
use Pimcore\Telemetry\Snapshot\SnapshotCollectorInterface;
use Pimcore\Telemetry\Snapshot\StatementCounter;
use Pimcore\Tests\Support\Test\TestCase;
use TypeError;

class SnapshotBuilderTest extends TestCase
{
    public function testCollectorsAreMergedUnderTheirNamespace(): void
    {
        $snapshot = (new SnapshotBuilder([
            $this->collector('core', ['asset_count_bucket' => '11-100', 'php_version' => '8.4.0']),
            $this->collector('datahub', ['graphql_configs' => '1-10', 'rest_enabled' => true]),
        ]))->build();

        unset($snapshot['meta.duration_ms']); // self-reporting, covered separately

        $this->assertSame(
            [
                'core.asset_count_bucket' => '11-100',
                'core.php_version' => '8.4.0',
                'datahub.graphql_configs' => '1-10',
                'datahub.rest_enabled' => true,
            ],
            $snapshot
        );
    }

    public function testWithoutCollectorsOnlyTheSelfReportRemains(): void
    {
        $this->assertSame(['meta.duration_ms'], array_keys((new SnapshotBuilder([]))->build()));
    }

    public function testReportsItsOwnDuration(): void
    {
        $snapshot = (new SnapshotBuilder([]))->build();

        $this->assertArrayHasKey('meta.duration_ms', $snapshot);
        $this->assertIsInt($snapshot['meta.duration_ms']);
        $this->assertGreaterThanOrEqual(0, $snapshot['meta.duration_ms']);
    }

    public function testDbStatementsIsOmittedWhenNoCounterIsWired(): void
    {
        $this->assertArrayNotHasKey('meta.db_statements', (new SnapshotBuilder([]))->build());
    }

    public function testDbStatementsDiscountsTheClosingProbe(): void
    {
        // Session counter reads 100 before and 111 after; the closing read is one of those
        // statements, so the work in between is 10.
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturnOnConsecutiveCalls(
            ['Value' => '100'],
            ['Value' => '111'],
        );

        $snapshot = (new SnapshotBuilder([], new StatementCounter($connection)))->build();

        $this->assertSame(10, $snapshot['meta.db_statements']);
    }

    /**
     * Collectors are a public extension point, and Maintenance\Executor only catches Exception - so
     * an Error escaping a collector would abort the whole maintenance run, not just telemetry. The
     * builder is the isolation boundary: a broken collector is logged and skipped, the healthy ones
     * still report.
     */
    public function testOneFailingCollectorDoesNotLoseTheOthers(): void
    {
        $exploding = new class implements SnapshotCollectorInterface {
            public function getNamespace(): string
            {
                return 'boom';
            }

            public function collect(): array
            {
                throw new TypeError('a defect inside a third-party collector');
            }
        };

        $snapshot = (new SnapshotBuilder([
            $exploding,
            $this->collector('core', ['php_version' => '8.4.0']),
        ]))->build();

        $this->assertSame('8.4.0', $snapshot['core.php_version'], 'healthy collectors must still report');
        $this->assertArrayNotHasKey('boom.anything', $snapshot);
        $this->assertArrayHasKey('meta.duration_ms', $snapshot, 'the snapshot itself must still complete');
    }

    public function testDbStatementsIsOmittedWhenTheCounterIsUnavailable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn(false);

        $snapshot = (new SnapshotBuilder([], new StatementCounter($connection)))->build();

        $this->assertArrayNotHasKey('meta.db_statements', $snapshot);
    }

    /**
     * @param array<string, mixed> $metrics
     */
    private function collector(string $namespace, array $metrics): SnapshotCollectorInterface
    {
        return new class($namespace, $metrics) implements SnapshotCollectorInterface {
            /**
             * @param array<string, mixed> $metrics
             */
            public function __construct(
                private readonly string $namespace,
                private readonly array $metrics,
            ) {
            }

            public function getNamespace(): string
            {
                return $this->namespace;
            }

            public function collect(): array
            {
                return $this->metrics;
            }
        };
    }
}
