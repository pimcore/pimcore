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

namespace Pimcore\Telemetry\Relay;

/**
 * Sends an encrypted telemetry batch to the first-party relay. The seam keeps the maintenance drain
 * task unit testable and lets the delivery mechanism evolve independently.
 *
 * @internal
 */
interface RelayClientInterface
{
    /**
     * Whether a relay endpoint is configured (nothing can be delivered otherwise).
     */
    public function isConfigured(): bool;

    /**
     * POST the encrypted batch to the relay. Returns true ONLY on a confirmed acceptance
     * (HTTP 2xx + an `{status: ok}` body); any other outcome returns false so the caller keeps the
     * batch for a later retry. Never throws - telemetry is best-effort.
     */
    public function send(string $instanceIdentifier, string $ciphertext): bool;
}
