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
use Pimcore\Telemetry\Spool\TelemetrySpool;

/**
 * Keeps the telemetry outbox bounded: reclaims leases whose drain never completed and deletes
 * events past their TTL. Runs unconditionally (even when telemetry is disabled) so a spool that
 * stopped being drained - e.g. nobody opens the UI - cannot grow without limit.
 *
 * @internal
 */
final readonly class TelemetrySpoolGcTask implements TaskInterface
{
    public function __construct(
        private TelemetrySpool $spool,
    ) {
    }

    public function execute(): void
    {
        $this->spool->releaseExpiredClaims();
        $this->spool->gc();
    }
}
