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

namespace Pimcore\Telemetry\Crypto;

use RuntimeException;

/**
 * Thrown when a telemetry envelope cannot be encrypted or (more importantly) decrypted - a wrong
 * product key, a tampered or truncated blob, or an unsupported format version. On the relay a
 * decryption failure is the authentication failure: a batch that will not decrypt is rejected.
 *
 * @internal
 */
final class EnvelopeCipherException extends RuntimeException
{
}
