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
use Pimcore\Telemetry\Snapshot\CountMap;
use Pimcore\Telemetry\Snapshot\QueueCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use function json_encode;

class QueueCollectorTest extends TestCase
{
    private const DOCTRINE = 'doctrine://default?queue_name=';

    /**
     * @var string[]
     */
    private array $executedSql = [];

    public function testNamespaceIsQueue(): void
    {
        $this->assertSame('queue', $this->collector()->getNamespace());
    }

    /**
     * Only the scheme of the transport DSN is reported, as one of a fixed set of names.
     */
    public function testReportsTheTransportSchemeOnly(): void
    {
        $this->assertSame('doctrine', $this->collector()->collect()['transport'] ?? null);
        $this->assertSame(
            'redis',
            $this->collector(dsnPrefix: 'redis://user:secret@cache.internal:6379/messages?auto_setup=false')
                ->collect()['transport'] ?? null,
        );
        $this->assertSame(
            'amqp',
            $this->collector(dsnPrefix: 'amqp://guest:guest@rabbit:5672/%2f/')->collect()['transport'] ?? null,
        );
        $this->assertSame('in_memory', $this->collector(dsnPrefix: 'in-memory://')->collect()['transport'] ?? null);
        $this->assertSame('other', $this->collector(dsnPrefix: 'enqueue://default')->collect()['transport'] ?? null);
    }

    public function testNothingOfTheDsnBeyondTheSchemeLeaves(): void
    {
        $metrics = $this->collector(dsnPrefix: 'redis://user:secret@cache.internal:6379/messages')->collect();

        $this->assertStringNotContainsString('secret', (string) json_encode($metrics));
        $this->assertStringNotContainsString('cache.internal', (string) json_encode($metrics));
    }

    public function testReportsDepthPerQueueInTotalAndTheFailedShare(): void
    {
        $metrics = $this->collector(
            queues: ['pimcore_core' => 3, 'pimcore_asset_update' => 5, 'pimcore_generic_data_index_failed' => 2],
        )->collect();

        $this->assertSame(10, $metrics['depth_total'] ?? null);
        $this->assertSame(
            ['pimcore_asset_update' => 5, 'pimcore_core' => 3, 'other' => 2],
            $metrics['depth_by_queue'] ?? null,
            'ranked by depth, ties by name; a bundle queue is not core and folds into other',
        );
        $this->assertSame(2, $metrics['failed_count'] ?? null);
        $this->assertArrayNotHasKey('oldest_message_age_s', $metrics, 'the age was dropped on purpose');
    }

    /**
     * Only the transports core itself configures and Symfony's conventional `failed` transport are
     * named. A `pimcore_` prefix is no proof of ownership - a project can call its own queue
     * `pimcore_customer_import` - so everything else, bundle or project, is one `other` figure.
     * Failed messages are counted wherever they sit.
     */
    public function testOnlyCoreQueuesAndTheFailedTransportAreNamed(): void
    {
        $metrics = $this->collector(
            queues: ['pimcore_core' => 1, 'pimcore_customer_import' => 4, 'failed' => 2, 'acme_orders_failed' => 1],
        )->collect();

        $this->assertSame(['other' => 5, 'failed' => 2, 'pimcore_core' => 1], $metrics['depth_by_queue'] ?? null);
        $this->assertSame(3, $metrics['failed_count'] ?? null);
        $this->assertStringNotContainsString('customer', (string) json_encode($metrics));
        $this->assertStringNotContainsString('acme', (string) json_encode($metrics));
    }

    public function testAnEmptyQueueReportsZeroDepth(): void
    {
        $metrics = $this->collector(queues: [])->collect();

        $this->assertSame(0, $metrics['depth_total'] ?? null);
        $this->assertSame([], $metrics['depth_by_queue'] ?? null);
        $this->assertSame(0, $metrics['failed_count'] ?? null);
    }

    /**
     * Only the Doctrine transport keeps its queues in a table this snapshot can read; anything else is
     * unknown, and no query is even attempted.
     */
    public function testDepthIsUnknownOnANonDoctrineTransport(): void
    {
        $metrics = $this->collector(dsnPrefix: 'redis://localhost/messages')->collect();

        $this->assertSame('redis', $metrics['transport'] ?? null);
        foreach (['depth_total', 'depth_by_queue', 'failed_count'] as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }
        $this->assertSame([], $this->executedSql);
    }

    public function testAFailedDepthQueryOmitsTheDepthKeysButKeepsTheTransport(): void
    {
        $metrics = $this->collector(failDepth: true)->collect();

        $this->assertSame('doctrine', $metrics['transport'] ?? null);
        foreach (['depth_total', 'depth_by_queue', 'failed_count'] as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }
    }

    /**
     * Depth means waiting: messages a worker has already picked up are not backlog.
     */
    public function testOnlyWaitingMessagesAreCountedWithOneQuery(): void
    {
        $this->collector()->collect();

        $this->assertCount(1, $this->executedSql);
        $this->assertStringContainsString('delivered_at IS NULL', $this->executedSql[0]);
    }

    /**
     * @param array<string, int> $queues queue name => waiting messages, as the GROUP BY returns them
     */
    private function collector(
        string $dsnPrefix = self::DOCTRINE,
        array $queues = ['pimcore_core' => 1],
        bool $failDepth = false,
    ): QueueCollector {
        $this->executedSql = [];

        $connection = $this->createStub(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchAllKeyValue')->willReturnCallback(
            function (string $sql) use ($queues, $failDepth): array {
                $this->executedSql[] = $sql;
                if ($failDepth) {
                    // stands in for what the per-statement timeout surfaces as
                    throw new RuntimeException('max_statement_time exceeded');
                }

                return $queues;
            }
        );
        // any single-value query is recorded and answered, so a stray second statement cannot hide
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql): int {
                $this->executedSql[] = $sql;

                return 42;
            }
        );

        return new QueueCollector(new SnapshotQueryRunner($connection, 0), new CountMap(), $dsnPrefix);
    }
}
