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

namespace Pimcore\Telemetry\Snapshot;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function str_ends_with;
use function str_starts_with;
use function strstr;
use function strtolower;

/**
 * The `queue.*` namespace: how Pimcore's asynchronous work is transported and how much of it is waiting.
 *
 * The transport is the scheme of the DSN prefix every Pimcore queue is configured with, reported as one of
 * a fixed set of names - nothing else of the DSN (host, credentials, database) is looked at. Depth is
 * only observable on the Doctrine transport, whose queues share the `messenger_messages` table; on any
 * other transport the depth keys are simply absent (unknown), and no query is attempted.
 *
 * Depth means waiting: rows a worker has already picked up (`delivered_at` set) are not backlog. The
 * per-queue map names Pimcore's own `pimcore_*` queues and Symfony's conventional `failed` transport;
 * every other queue is a project's vocabulary and is folded into `other`. Failed messages are counted
 * wherever they sit, by the `_failed` naming convention of the failure transports.
 *
 * @internal
 */
final readonly class QueueCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    private const TABLE = 'messenger_messages';

    public function __construct(
        private SnapshotQueryRunner $queryRunner,
        private CountMapInterface $countMap,
        #[Autowire('%pimcore.messenger.transport_dsn_prefix%')]
        private string $transportDsnPrefix,
    ) {
    }

    public function getNamespace(): string
    {
        return 'queue';
    }

    public function collect(): array
    {
        $transport = $this->transport();
        $metrics = [
            'schema_version' => self::SCHEMA_VERSION,
            'transport' => $transport,
        ];

        if ($transport !== 'doctrine') {
            return $metrics;
        }

        return $metrics + ($this->depth() ?? []);
    }

    private function transport(): string
    {
        $scheme = strtolower((string) strstr($this->transportDsnPrefix, '://', true));

        return match ($scheme) {
            'doctrine', 'redis', 'amqp', 'sqs', 'beanstalkd', 'sync' => $scheme,
            'in-memory' => 'in_memory',
            default => 'other',
        };
    }

    /**
     * @return array<string, int|array<string, int>>|null null when the table cannot be read
     */
    private function depth(): ?array
    {
        $table = $this->queryRunner->quoteIdentifier(self::TABLE);

        try {
            $rows = $this->queryRunner->fetchAllKeyValue(
                'SELECT queue_name, COUNT(*) FROM ' . $table . ' WHERE delivered_at IS NULL GROUP BY queue_name'
            );
        } catch (Exception) {
            return null;
        }

        $byQueue = [];
        $total = 0;
        $failed = 0;

        foreach ($rows as $queue => $count) {
            $queue = (string) $queue;
            $count = (int) $count;
            $total += $count;

            if ($this->isFailureQueue($queue)) {
                $failed += $count;
            }

            $key = $this->queueKey($queue);
            $byQueue[$key] = ($byQueue[$key] ?? 0) + $count;
        }

        return [
            'depth_total' => $total,
            'depth_by_queue' => $this->countMap->ranked($byQueue),
            'failed_count' => $failed,
        ];
    }

    private function isFailureQueue(string $queue): bool
    {
        return $queue === 'failed' || str_ends_with($queue, '_failed');
    }

    private function queueKey(string $queue): string
    {
        return $queue === 'failed' || str_starts_with($queue, 'pimcore_') ? $queue : 'other';
    }
}
