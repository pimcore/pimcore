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

/**
 * Deliberately declared with no namespace (i.e. outside `Pimcore\`), and deliberately not
 * autoloaded via PSR-4 - every real class in this suite lives under `Pimcore\Tests\`, which
 * would satisfy the namespace allowlist and defeat the point of this fixture. It stands in for
 * any class with a side-effecting magic method that must never be instantiated when reading
 * forged/untrusted `tmp_store` session data (see ServiceTest::testGetElementFromSessionRejects...).
 *
 * The declaration is guarded because the test runner may load this file more than once, and an
 * unconditional declaration would then abort the whole run with "Cannot redeclare class".
 */
if (!class_exists('UntrustedClassProbe', false)) {
    class UntrustedClassProbe
    {
        public static bool $wasInstantiated = false;

        public function __wakeup(): void
        {
            self::$wasInstantiated = true;
        }
    }
}
