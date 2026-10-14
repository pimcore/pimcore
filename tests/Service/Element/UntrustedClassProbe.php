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
 * Stands in for any class with a side-effecting magic method that must never be instantiated when
 * reading unsigned/untrusted `tmp_store` session data (see ServiceTest::testGetElementFromSessionRejects...).
 * Deliberately declared with no namespace and not autoloaded via PSR-4, so the test controls when it is loaded.
 */
class UntrustedClassProbe
{
    public static bool $wasInstantiated = false;

    public function __wakeup(): void
    {
        self::$wasInstantiated = true;
    }
}
