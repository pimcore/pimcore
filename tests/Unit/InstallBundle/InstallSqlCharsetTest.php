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

namespace Pimcore\Tests\Unit\InstallBundle;

use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for pimcore/internal-improvements#16 — install.sql used the deprecated,
 * ambiguous `utf8`/`utf8_bin`/`utf8_general_ci` names (aliases for utf8mb3, deprecated since
 * MySQL 8.0.28). Columns that are part of a composite index sized to the 3072-byte InnoDB
 * index-prefix limit (documented inline as "using the full key length of 3072 bytes") must
 * stay 3 bytes/char, so they use the explicit, non-deprecated `utf8mb3` name instead. All
 * other columns must use full `utf8mb4`.
 */
class InstallSqlCharsetTest extends TestCase
{
    public function testInstallSqlDoesNotUseDeprecatedUtf8Alias(): void
    {
        $sql = file_get_contents(PIMCORE_PROJECT_ROOT . '/bundles/InstallBundle/dump/install.sql');

        $this->assertDoesNotMatchRegularExpression(
            '/CHARACTER SET utf8(?!mb[34])\b/i',
            $sql,
            'install.sql must not use the deprecated "utf8" charset alias, use "utf8mb3" (index-length-constrained columns) or "utf8mb4" instead.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/COLLATE[= ]utf8(?!mb[34])_/i',
            $sql,
            'install.sql must not use the deprecated "utf8_*" collations, use "utf8mb3_*" (index-length-constrained columns) or "utf8mb4_*" instead.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/CHARSET=utf8(?!mb[34])\b/i',
            $sql,
            'install.sql must not use the deprecated "utf8" table charset alias, use "utf8mb4" instead.'
        );
    }

    public function testLockKeysTableUsesUtf8mb4(): void
    {
        $sql = file_get_contents(PIMCORE_PROJECT_ROOT . '/bundles/InstallBundle/dump/install.sql');

        $this->assertMatchesRegularExpression(
            '/CREATE TABLE `lock_keys`.*?;/s',
            $sql,
            'lock_keys table definition not found in install.sql'
        );
        preg_match('/CREATE TABLE `lock_keys`.*?;/s', $sql, $matches);
        $lockKeysStatement = $matches[0];

        $this->assertStringContainsString(
            'CHARSET=utf8mb4',
            $lockKeysStatement,
            'the lock_keys table must use utf8mb4, not the deprecated utf8 default charset.'
        );
    }
}
