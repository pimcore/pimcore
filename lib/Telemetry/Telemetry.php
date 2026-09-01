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

    /**
     * Truncation for the domain HMAC. 16 hex characters (64 bits) is far more than the handful of
     * deployments per installation needs, and a collision would only ever merge two of them.
     */
    private const DOMAIN_HASH_LENGTH = 16;

    /**
     * Stands in for the domain hash when `pimcore.general.domain` is not configured, which is the
     * default and therefore the common case.
     *
     * Purely so the key is self-describing: a trailing empty segment reads as a truncation bug to
     * anyone looking at it in analytics, whereas this says plainly that the deployment could not be
     * identified beyond its mode. It buys no extra resolution - every unconfigured deployment of one
     * installation in one mode still shares this segment, which is exactly what an empty one did.
     */
    private const DOMAIN_UNKNOWN = 'unknown';

    public function __construct(
        private readonly string $instanceIdentifier,
        private readonly string $productKey,
        private readonly TelemetrySpoolWriterInterface $spool,
        private readonly LoggerInterface $logger,
        private readonly EventSanitizer $sanitizer,
        private readonly string $environment = '',
        private readonly string $mainDomain = '',
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
            'groups' => [self::INSTANCE_GROUP_TYPE => $this->instanceGroupKey()] + $groups,
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
            // The instance group belongs to this class: `capture()` has always injected it with
            // precedence over anything a caller passes, and the profile has to land on exactly the
            // group the events are attributed to or the two would describe different things.
            'groupKey' => $type === self::INSTANCE_GROUP_TYPE ? $this->instanceGroupKey() : $key,
            'properties' => $this->sanitizer->sanitizeProperties($properties, 'group:' . $type),
        ];
    }

    /**
     * Identifies the **deployment**, not the installation.
     *
     * One product key is routinely installed more than once: staging is usually a restore of
     * production, so it carries the same instance identifier, product key and encryption secret - and
     * because the key is bound to (identifier, encryption secret) by `validateProductKey()`, a clone
     * that boots at all is identical in every registration-derived value. Keyed on the identifier
     * alone those deployments share one group, and since a group profile is last-write-wins their
     * snapshots overwrite each other: a staging box with forty test objects would flip production's
     * counts every other day.
     *
     * The domain is what actually separates them, and it is HMAC'd rather than sent: analytics only
     * ever sees an opaque token, so the dataset stays pseudonymous instead of being attributable to a
     * named company. Keying the HMAC with the product key means the same domain under a different
     * installation hashes differently - no cross-instance correlation, and no dictionary attack for
     * anyone without the key.
     *
     * `pimcore.general.domain` is optional config, so an unconfigured domain is normal: the segment
     * falls back to {@see self::DOMAIN_UNKNOWN} and the key still separates environments, it just
     * cannot separate two deployments running the same one.
     *
     * The environment is the raw kernel value, matching `core.environment_name` in the snapshot and
     * `environment_name` in {@see \Pimcore\Tool\StatisticsManager} so all three line up. Two things
     * follow from putting a free-text value in an identity: the key inherits whatever `APP_ENV`
     * contains, and its cardinality is unbounded - renaming an environment, or a stray character in
     * it, produces a different group rather than a changed one.
     */
    private function instanceGroupKey(): string
    {
        $domainHash = $this->mainDomain === ''
            ? self::DOMAIN_UNKNOWN
            : substr(hash_hmac('sha256', $this->mainDomain, $this->productKey), 0, self::DOMAIN_HASH_LENGTH);

        return $this->instanceIdentifier
            . ':' . $this->environment
            . ':' . $domainHash;
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
