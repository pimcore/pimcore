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

/**
 * A leased batch, encrypted and ready for the relay. The {@see $ciphertext} is the product-key
 * encrypted inner envelope (`{instanceIdentifier, ts, events}`); {@see $instanceIdentifier} travels
 * in the clear so the relay can look the product key up to decrypt. Both the maintenance drain and
 * the Studio UI treat the ciphertext as opaque and forward it verbatim, then pass {@see $nonce} to
 * {@see TelemetryOutboxService::ack()} once the relay has accepted it.
 *
 * @internal
 */
final readonly class EncryptedBatch
{
    public function __construct(
        public string $nonce,
        public string $instanceIdentifier,
        public string $ciphertext,
        public int $count,
    ) {
    }
}
