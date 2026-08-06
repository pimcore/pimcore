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

/**
 * Server-side product telemetry. Other bundles depend on this interface to emit
 * behavior-only, content-never events to the first-party telemetry relay (which
 * forwards them to PostHog).
 *
 * All methods are no-ops when telemetry is disabled and never throw: failures are
 * logged, never propagated into a request or CLI run.
 *
 * @internal
 */
interface TelemetryInterface
{
    /**
     * Whether this instance can report at all: it must be identified and carry a product key.
     * When false every method below is a no-op and nothing leaves the instance.
     */
    public function isEnabled(): bool;

    /**
     * Capture a domain-meaningful event. Properties must contain behavior/counts/types
     * only - never field values, names, paths, or any customer content.
     *
     * @param array<string, mixed> $properties
     * @param array<string, string> $groups extra PostHog groups; the instance group is added automatically
     */
    public function capture(string $event, array $properties = [], array $groups = []): void;

    /**
     * Attach properties to a PostHog group (B2B account/instance rollup).
     *
     * @param array<string, mixed> $properties
     */
    public function groupIdentify(string $type, string $key, array $properties): void;

    /**
     * Send the buffered events to the relay as a single batch. Call this before the
     * process exits (CLI/maintenance).
     */
    public function flush(): void;
}
