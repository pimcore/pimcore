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
 * ambiguous `utf8`/`utf8_bin`/`utf8_general_ci` names. Most columns must use full `utf8mb4`.
 * The only exception is `assets`.`filename`/`path`, `documents`.`key`/`path` and
 * `objects`.`key`/`path` (3 tables x 2 columns = 6 declarations): their composite `fullpath`
 * unique index (path+filename or path+key, 765+255 chars) already uses the full 3072-byte
 * InnoDB index-prefix budget at 3 bytes/char and would overflow it at 4 bytes/char, so they
 * use the explicit `utf8mb3` name instead of the ambiguous `utf8` alias. Note MySQL has
 * deprecated `utf8mb3` itself too (not just `utf8`) — this is a documented stopgap pending an
 * index/schema redesign, not a modern target state, so this test locks the exception down to
 * exactly those 6 declarations rather than exempting `utf8mb3` wholesale.
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

    public function testUtf8mb3IsConfinedToTheKnownIndexWidthExceptions(): void
    {
        $sql = file_get_contents(PIMCORE_PROJECT_ROOT . '/bundles/InstallBundle/dump/install.sql');

        preg_match_all('/^.*CHARACTER SET utf8mb3.*$/m', $sql, $matches);
        $utf8mb3Lines = $matches[0];

        $this->assertCount(
            6,
            $utf8mb3Lines,
            'utf8mb3 must be confined to assets.filename/path, documents.key/path and objects.key/path (6 declarations) - '
                . 'if this count changed, either a new exception was introduced (verify it truly cannot fit utf8mb4) or '
                . 'one of the existing ones was fixed for real (update this test to lock in the new, smaller exception set).'
        );

        foreach ($utf8mb3Lines as $line) {
            $this->assertMatchesRegularExpression(
                '/`(filename|key|path)`/',
                $line,
                'unexpected utf8mb3 usage outside the known index-width-constrained columns: ' . $line
            );
        }
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
