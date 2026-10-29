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

namespace Pimcore\Tests\Model\DataType;

/**
 * @internal
 *
 * Stands in for a POP-chain gadget in GHSA-6324-x2cv-h975-style tests. Its __wakeup() fires the
 * instant unserialize() accepts its class, mirroring the point at which a real gadget (e.g. a
 * Monolog handler) would run attacker code. A class outside unserialize()'s allowed_classes list
 * is reconstructed as __PHP_Incomplete_Class instead, and __wakeup() is never called on it - so
 * this flag staying false is what proves the allowlist held.
 */
final class UnserializeWakeupProbe
{
    public static bool $wokeUp = false;

    public function __wakeup(): void
    {
        self::$wokeUp = true;
    }
}
