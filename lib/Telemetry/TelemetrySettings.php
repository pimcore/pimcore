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
 * Fixed settings for product telemetry.
 *
 * These are constants rather than configuration on purpose: the relay is a first-party service at a
 * known address, and the collection cadence and outbox bounds are product decisions that must behave
 * identically on every installation. {@see \Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension}
 * publishes them as `pimcore.telemetry.*` container parameters, so every consuming service keeps a
 * single constructor seam that tests can drive with other values.
 *
 * Whether an instance reports at all is not a setting either: telemetry is inert unless the instance
 * is identified and carries a product key ({@see Telemetry::isEnabled()}) - the same secret the relay
 * needs to decrypt, and thereby authenticate, that instance's batches.
 *
 * @internal
 */
final class TelemetrySettings
{
    /**
     * First-party relay. It owns the PostHog key; instances never talk to PostHog directly.
     */
    public const RELAY_ENDPOINT = 'https://license.pimcore.com/telemetry/v1/ingest';

    /**
     * The deployment snapshot is a slow-changing structural census, so it is produced at most once
     * per day no matter how often the maintenance job runs.
     */
    public const SNAPSHOT_INTERVAL_SECONDS = 86400;

    /**
     * Per-query execution-time cap for the snapshot collectors. A structural aggregate over a
     * multi-million-row table is aborted rather than allowed to stall the maintenance run; the
     * affected metric degrades to 0 for that cycle.
     */
    public const SNAPSHOT_QUERY_TIMEOUT_SECONDS = 5;

    /**
     * Pending events retained before new ones are dropped - bounds disk use if nothing drains.
     */
    public const SPOOL_CAP = 10000;

    /**
     * Spooled events older than this many days are garbage-collected.
     */
    public const SPOOL_TTL_DAYS = 30;

    /**
     * How long a claimed (in-flight) batch stays leased before an unacked drain may reclaim it.
     */
    public const SPOOL_LEASE_SECONDS = 600;
}
