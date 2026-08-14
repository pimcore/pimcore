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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Telemetry\Relay\RelayClientInterface;
use Pimcore\Telemetry\Spool\TelemetryOutboxInterface;

/**
 * The primary telemetry drainer: on each maintenance run it forwards spooled batches to the relay.
 *
 * Transactional outbox discipline - **a batch leaves the pool only after the relay confirms it**:
 * claim (lease) -> encrypt -> POST -> ack on success, release on failure. On the first failure it
 * stops and leaves the rest for the next run (or for the Studio UI to drain), so a relay outage or a
 * DMZ with no outbound access simply lets the pool fill until it can be delivered. A per-run cap
 * bounds how long one maintenance tick spends here.
 *
 * @internal
 */
final readonly class TelemetrySpoolDrainTask implements TaskInterface
{
    private const MAX_BATCHES_PER_RUN = 20;

    public function __construct(
        private TelemetryOutboxInterface $outbox,
        private RelayClientInterface $relay,
    ) {
    }

    public function execute(): void
    {
        // isReady() is the gate: an unidentified instance, or one without a product key, cannot
        // produce a decryptable batch, so there is nothing to drain.
        if (!$this->outbox->isReady() || !$this->relay->isConfigured()) {
            return;
        }

        for ($i = 0; $i < self::MAX_BATCHES_PER_RUN; $i++) {
            $batch = $this->outbox->nextBatch();

            if ($batch === null) {
                // pool drained
                return;
            }

            if ($this->relay->send($batch->instanceIdentifier, $batch->ciphertext)) {
                $this->outbox->ack($batch->nonce);

                continue;
            }

            // Delivery failed: hand the batch back and stop; retry on the next run.
            $this->outbox->release($batch->nonce);

            return;
        }
    }
}
