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

namespace Pimcore\Telemetry;

use Pimcore\Telemetry\Spool\TelemetrySpoolWriterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Buffering implementation of {@see TelemetryInterface}.
 *
 * Events are collected in memory (behavior-only, content-never, guarded by {@see EventSanitizer})
 * and, on {@see self::flush()}, persisted to the durable outbox ({@see TelemetrySpoolWriterInterface}).
 * Nothing is sent inline: the maintenance job and the Studio UI later drain the outbox, encrypt each
 * batch with the instance's product key, and forward it to the first-party relay. The instance never
 * talks to PostHog directly and holds no PostHog API key.
 *
 * Telemetry is active only when the instance is identified AND a product key is present - the product
 * key is the shared secret the relay uses to decrypt (and thereby authenticate) this instance's
 * batches, so without it nothing could be delivered anyway. That also makes unlicensed installations
 * (development checkouts, CI runs) inert without any configuration.
 *
 * @internal
 */
final class Telemetry implements TelemetryInterface
{
    private const INSTANCE_GROUP_TYPE = 'instance';

    /**
     * @var list<array<string, mixed>>
     */
    private array $buffer = [];

    public function __construct(
        private readonly string $instanceIdentifier,
        private readonly string $productKey,
        private readonly TelemetrySpoolWriterInterface $spool,
        private readonly LoggerInterface $logger,
        private readonly EventSanitizer $sanitizer,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->instanceIdentifier !== '' && $this->productKey !== '';
    }

    public function capture(string $event, array $properties = [], array $groups = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (!$this->sanitizer->isValidEventName($event)) {
            $this->logger->warning('Telemetry event name does not follow the taxonomy convention', [
                'event' => $event,
            ]);
        }

        $this->buffer[] = [
            'type' => 'capture',
            'event' => $event,
            'properties' => $this->sanitizer->sanitizeProperties($properties, $event),
            'groups' => [self::INSTANCE_GROUP_TYPE => $this->instanceIdentifier] + $groups,
        ];
    }

    public function groupIdentify(string $type, string $key, array $properties): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->buffer[] = [
            'type' => 'group_identify',
            'groupType' => $type,
            'groupKey' => $key,
            'properties' => $this->sanitizer->sanitizeProperties($properties, 'group:' . $type),
        ];
    }

    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $events = $this->buffer;

        // Clear the buffer up front so a failed enqueue is never retried implicitly on the next
        // flush (telemetry is best-effort and must never disrupt the host process). Durability is
        // the spool's job from here: once written, the maintenance job / UI drain deliver it.
        $this->buffer = [];

        try {
            $this->spool->enqueue($events);
        } catch (Throwable $exception) {
            $this->logger->error('Telemetry flush to spool failed', ['exception' => $exception]);
        }
    }
}
