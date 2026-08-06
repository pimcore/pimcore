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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use function microtime;
use function round;

/**
 * Builds the telemetry snapshot (Layer 1): runs every tagged
 * {@see SnapshotCollectorInterface} and merges their output into a single, flat,
 * namespaced array ready to be sent as PostHog group properties.
 *
 * It also reports on itself under `meta.*` - how long collecting took and how many statements it
 * cost - so the fleet data shows whether the snapshot is getting slower or heavier on real
 * installations, which is the one thing that cannot be measured on a development machine. Both are
 * plain integers about our own job, never anything about the customer.
 *
 * @internal
 */
final readonly class SnapshotBuilder
{
    /**
     * @param iterable<SnapshotCollectorInterface> $collectors
     */
    public function __construct(
        private iterable $collectors,
        private ?StatementCounter $statementCounter = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $statementsBefore = $this->statementCounter?->read();
        $startedAt = microtime(true);

        $snapshot = [];
        foreach ($this->collectors as $collector) {
            // This is the fault-isolation boundary for collectors, which are a public extension
            // point: a bundle's collector must not be able to take down the snapshot - nor, since
            // Maintenance\Executor only catches Exception, the whole maintenance run. Everything
            // inside a collector therefore narrows to Exception and any real defect surfaces here,
            // logged with its namespace, while the remaining collectors still report.
            try {
                $namespace = $collector->getNamespace();
                $metrics = $collector->collect();
            } catch (Throwable $exception) {
                $this->logger->error('Telemetry snapshot collector failed', [
                    'collector' => $collector::class,
                    'exception' => $exception,
                ]);

                continue;
            }

            foreach ($metrics as $key => $value) {
                $snapshot[$namespace . '.' . $key] = $value;
            }
        }

        $snapshot['meta.duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        $statements = $this->statementCounter?->between($statementsBefore, $this->statementCounter->read());
        if ($statements !== null) {
            $snapshot['meta.db_statements'] = $statements;
        }

        return $snapshot;
    }
}
