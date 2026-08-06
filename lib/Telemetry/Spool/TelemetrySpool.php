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

namespace Pimcore\Telemetry\Spool;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Throwable;
use function bin2hex;
use function count;
use function is_array;
use function json_decode;
use function json_encode;
use function max;
use function random_bytes;

/**
 * Durable outbox (transactional-outbox pattern) for telemetry that cannot be sent inline.
 *
 * Events are enqueued as they are collected and drained later by the Studio UI: a drain claims a
 * batch (leasing the rows with a nonce so concurrent tabs can't grab the same ones), the browser
 * forwards it to the relay, then acks (delete) - or releases (unlease) on failure. Unacked leases
 * expire and become claimable again, and each row carries a stable event_uid the relay dedupes on,
 * so an occasional double-send is harmless.
 *
 * The `telemetry_spool` table is provisioned by the installer (`install.sql`) for new installs and
 * by migration `Version20260720120000` for existing ones - it is never created at runtime, so no
 * request path issues DDL. All rows hold already-sanitized, content-never events.
 *
 * @internal
 */
final class TelemetrySpool implements TelemetrySpoolWriterInterface, TelemetrySpoolReaderInterface
{
    private const TABLE = 'telemetry_spool';

    private const DEFAULT_CAP = 10000;

    private const DEFAULT_CLAIM_LIMIT = 500;

    private const DEFAULT_LEASE_SECONDS = 600;

    private const DEFAULT_TTL_DAYS = 30;

    /**
     * A batch the relay keeps rejecting for a non-transient reason (an instance it does not know, a
     * payload it will never accept) would otherwise be released and re-claimed forever, blocking
     * everything behind it until the TTL expires. After this many failed deliveries a row stops
     * being claimable and is cleaned up by the next {@see self::gc()}.
     */
    private const MAX_DELIVERY_ATTEMPTS = 5;

    public function __construct(
        private readonly Connection $connection,
        private readonly int $cap = self::DEFAULT_CAP,
        private readonly int $ttlDays = self::DEFAULT_TTL_DAYS,
        private readonly int $leaseSeconds = self::DEFAULT_LEASE_SECONDS,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Append events to the outbox. Bounded: once the pending backlog hits $cap we shed new events
     * rather than grow without limit (an instance whose UI is never opened must not fill its disk).
     *
     * @param list<array<string, mixed>> $events
     */
    public function enqueue(array $events, ?int $cap = null): void
    {
        if ($events === []) {
            return;
        }

        $cap = $cap ?? $this->cap;
        $pending = $this->countPending();
        $table = $this->connection->quoteIdentifier(self::TABLE);
        $shed = 0;

        foreach ($events as $event) {
            // Re-checked per event, not once per call: a single flush can carry far more events
            // than the whole cap (a CLI import buffers one per created element).
            if ($pending >= $cap) {
                $shed++;

                continue;
            }

            $payload = json_encode($event);

            if ($payload === false) {
                // Never persist a non-encodable event (would poison the drain's json_decode).
                continue;
            }

            $this->connection->executeStatement(
                'INSERT INTO ' . $table . ' (event_uid, created_at, payload) VALUES (?, NOW(), ?)',
                [$this->uid(), $payload]
            );
            $pending++;
        }

        if ($shed > 0) {
            // Silent shedding looks identical to "this instance reports nothing" in support.
            $this->logger?->warning(
                'Telemetry spool is full ({cap} pending); dropped {shed} of {total} new events. '
                . 'The outbox is not being drained - check relay reachability or the maintenance job.',
                ['cap' => $cap, 'shed' => $shed, 'total' => count($events)]
            );
        }
    }

    /**
     * Lease the oldest pending events under a fresh nonce and return them for forwarding.
     * Returns null when nothing is pending.
     */
    public function claim(int $limit = self::DEFAULT_CLAIM_LIMIT, ?int $leaseSeconds = null): ?SpooledBatch
    {
        $this->releaseExpiredClaims($leaseSeconds ?? $this->leaseSeconds);

        $table = $this->connection->quoteIdentifier(self::TABLE);
        $nonce = $this->uid();
        $limit = max(1, $limit);

        $claimed = (int)$this->connection->executeStatement(
            'UPDATE ' . $table . ' SET claimed_at = NOW(), claim_nonce = ?'
            . ' WHERE claim_nonce IS NULL AND attempts < ' . self::MAX_DELIVERY_ATTEMPTS
            . ' ORDER BY id LIMIT ' . $limit,
            [$nonce]
        );

        if ($claimed === 0) {
            return null;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_uid, payload FROM ' . $table . ' WHERE claim_nonce = ? ORDER BY id',
            [$nonce]
        );

        $events = [];
        foreach ($rows as $row) {
            $event = json_decode((string)$row['payload'], true);
            if (is_array($event)) {
                // stable dedupe key the relay uses to drop repeats from lease expiry / double drain
                $event['eventUid'] = $row['event_uid'];
                $events[] = $event;
            }
        }

        return new SpooledBatch($nonce, $events);
    }

    /**
     * Delete a leased batch after it was successfully forwarded.
     */
    public function ack(string $nonce): int
    {
        return (int)$this->connection->executeStatement(
            'DELETE FROM ' . $this->connection->quoteIdentifier(self::TABLE) . ' WHERE claim_nonce = ?',
            [$nonce]
        );
    }

    /**
     * Hand a leased batch back to the pending pool (forward failed).
     */
    public function release(string $nonce): int
    {
        return (int)$this->connection->executeStatement(
            'UPDATE ' . $this->connection->quoteIdentifier(self::TABLE)
            . ' SET claim_nonce = NULL, claimed_at = NULL, attempts = attempts + 1 WHERE claim_nonce = ?',
            [$nonce]
        );
    }

    /**
     * Reclaim leases whose drainer never acked (browser closed mid-drain).
     */
    public function releaseExpiredClaims(?int $leaseSeconds = null): int
    {
        $leaseSeconds = max(1, $leaseSeconds ?? $this->leaseSeconds);

        return (int)$this->connection->executeStatement(
            'UPDATE ' . $this->connection->quoteIdentifier(self::TABLE)
            . ' SET claim_nonce = NULL, claimed_at = NULL, attempts = attempts + 1'
            . ' WHERE claim_nonce IS NOT NULL AND claimed_at < (NOW() - INTERVAL ' . $leaseSeconds . ' SECOND)'
        );
    }

    /**
     * Delete events older than the TTL. Meant to run from the maintenance task.
     */
    public function gc(?int $ttlDays = null): int
    {
        $ttlDays = max(1, $ttlDays ?? $this->ttlDays);

        return (int)$this->connection->executeStatement(
            'DELETE FROM ' . $this->connection->quoteIdentifier(self::TABLE)
            . ' WHERE created_at < (NOW() - INTERVAL ' . $ttlDays . ' DAY)'
            . ' OR attempts >= ' . self::MAX_DELIVERY_ATTEMPTS
        );
    }

    public function countPending(): int
    {
        return (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . $this->connection->quoteIdentifier(self::TABLE) . ' WHERE claim_nonce IS NULL AND attempts < ' . self::MAX_DELIVERY_ATTEMPTS
        );
    }

    public function countClaimed(): int
    {
        return (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . $this->connection->quoteIdentifier(self::TABLE) . ' WHERE claim_nonce IS NOT NULL'
        );
    }

    /**
     * Read (without leasing) the oldest pending payloads - for inspection/debugging only.
     *
     * @return list<array<string, mixed>>
     */
    public function peekPending(int $limit = 10): array
    {
        $limit = max(1, $limit);

        $rows = $this->connection->fetchFirstColumn(
            'SELECT payload FROM ' . $this->connection->quoteIdentifier(self::TABLE)
            . ' WHERE claim_nonce IS NULL AND attempts < ' . self::MAX_DELIVERY_ATTEMPTS
            . ' ORDER BY id LIMIT ' . $limit
        );

        $events = [];
        foreach ($rows as $payload) {
            $event = json_decode((string)$payload, true);
            if (is_array($event)) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Whether the outbox table has been provisioned (installer or migration). The table is never
     * created at runtime, so tooling can report a clear "run your migrations" instead of surfacing
     * a driver error.
     */
    public function isProvisioned(): bool
    {
        try {
            $this->connection->fetchOne(
                'SELECT 1 FROM ' . $this->connection->quoteIdentifier(self::TABLE) . ' LIMIT 1'
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function uid(): string
    {
        return bin2hex(random_bytes(16));
    }

}
