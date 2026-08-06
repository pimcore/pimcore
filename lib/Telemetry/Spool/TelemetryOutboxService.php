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

use Pimcore\Telemetry\Crypto\EnvelopeCipher;
use Pimcore\Telemetry\Crypto\EnvelopeCipherException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use function time;

/**
 * Prepares the next batch for delivery: it claims a leased set of spooled events, wraps them in an
 * envelope, and **encrypts that envelope with the instance's product key**. The relay looks the same
 * product key up (by the cleartext instance identifier) and decrypts - which authenticates the batch,
 * so no separate signature or bearer token is needed.
 *
 * The same encrypted batch feeds both drainers: the maintenance job POSTs it to the relay directly,
 * and the Studio UI fetches it and forwards it from the browser. Because encryption happens here, on
 * the server, the browser only ever holds opaque ciphertext - it can neither read the telemetry nor
 * forge a batch. A batch leaves the pool (ack) only after the relay confirms it.
 *
 * @internal
 */
final class TelemetryOutboxService implements TelemetryOutboxInterface
{
    public function __construct(
        private readonly TelemetrySpoolReaderInterface $spool,
        private readonly EnvelopeCipher $cipher,
        private readonly string $instanceIdentifier,
        private readonly string $productKey,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Whether the outbox can hand out batches at all: we need an instance identity and a product key
     * (the relay could not decrypt - and so would reject - a batch produced without the key).
     */
    public function isReady(): bool
    {
        return $this->instanceIdentifier !== '' && $this->productKey !== '';
    }

    /**
     * Claim, wrap, and encrypt the next pending batch. Returns null when the spool is empty or the
     * batch could not be encrypted (in which case the rows are released, not lost).
     */
    public function nextBatch(): ?EncryptedBatch
    {
        try {
            $claimed = $this->spool->claim();
        } catch (Throwable $exception) {
            // The outbox is best-effort and is read from an HTTP endpoint (the Studio drain) as well
            // as from maintenance. A storage-level problem - most plausibly the table not existing
            // yet because the code was deployed before migrations ran - must read as "nothing to
            // send", never as a failed request.
            $this->logger->error('Telemetry outbox is unavailable', ['exception' => $exception]);

            return null;
        }

        if ($claimed === null) {
            return null;
        }

        $envelope = [
            'instanceIdentifier' => $this->instanceIdentifier,
            'ts' => time(),
            'events' => $claimed->events,
        ];

        try {
            $ciphertext = $this->cipher->encrypt($envelope, $this->productKey);
        } catch (EnvelopeCipherException) {
            // Could not encrypt - hand the rows back rather than lose or block them.
            $this->spool->release($claimed->nonce);

            return null;
        }

        return new EncryptedBatch($claimed->nonce, $this->instanceIdentifier, $ciphertext, $claimed->count());
    }

    public function ack(string $nonce): int
    {
        try {
            return $this->spool->ack($nonce);
        } catch (Throwable $exception) {
            $this->logger->error('Telemetry outbox ack failed', ['exception' => $exception]);

            return 0;
        }
    }

    public function release(string $nonce): int
    {
        try {
            return $this->spool->release($nonce);
        } catch (Throwable $exception) {
            $this->logger->error('Telemetry outbox release failed', ['exception' => $exception]);

            return 0;
        }
    }
}
