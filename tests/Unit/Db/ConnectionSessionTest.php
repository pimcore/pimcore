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

namespace Pimcore\Tests\Unit\Db;

use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The session settings of the connection that are applied through
 * `doctrine.dbal.connections.default.options` (PDO::MYSQL_ATTR_INIT_COMMAND).
 *
 * MariaDB >= 11.6.2 (11.8, 12.3) enables innodb_snapshot_isolation by default. A locking read (SELECT ... FOR
 * UPDATE, DELETE) of a row that another transaction committed after the snapshot of the current transaction is
 * then rejected with error 1020 and the transaction is rolled back, instead of reading the row as MySQL and
 * MariaDB <= 11.4 do. Pimcore is built for the latter semantics (e.g. the cleanup of scheduled tasks while saving
 * an element inside a transaction of the caller), so the connection switches the setting off for its own session.
 */
final class ConnectionSessionTest extends TestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

    public function testConnectionDoesNotEnforceInnodbSnapshotIsolation(): void
    {
        $variable = Db::get()->fetchAssociative("SHOW SESSION VARIABLES LIKE 'innodb\_snapshot\_isolation'");

        if ($variable === false) {
            // MySQL and old MariaDB versions do not have the variable, there is nothing to switch off
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertContains(strtolower((string) $variable['Value']), ['off', '0']);
    }

    public function testConnectionStillRunsWithAnEmptySqlMode(): void
    {
        $this->assertSame('', Db::get()->fetchOne('SELECT @@session.sql_mode'));
    }
}
