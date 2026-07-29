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

namespace Pimcore\Tests\Unit\Tool;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;

/**
 * Regression test for pimcore/internal-improvements#16 — checkMysql() used to run two
 * dead checks (innodb_large_prefix / innodb_file_format) against MySQL variables that were
 * removed in MySQL 8.0 / MariaDB 10.3+. Since fetchAssociative() always returns false for
 * an unknown variable, those checks could never report anything but STATE_OK and only added
 * noise; they have been removed from Requirements::checkMysql().
 */
class RequirementsTest extends TestCase
{
    private function mockConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchFirstColumn')->willReturn(['InnoDB']);

        $connection->method('fetchAssociative')->willReturnCallback(
            function (string $query) {
                if (str_contains($query, 'character_set_database')) {
                    return ['Value' => 'utf8mb4'];
                }

                if (str_contains($query, 'innodb_file_per_table')) {
                    return ['Value' => 'ON'];
                }

                // MySQL 8.0 / MariaDB 10.3+ no longer expose these variables at all.
                return false;
            }
        );

        $connection->method('executeQuery')->willReturn($this->createStub(Result::class));
        $connection->method('insert')->willReturn(1);
        $connection->method('fetchAllAssociative')->willReturn([]);

        return $connection;
    }

    public function testCheckMysqlNoLongerChecksRemovedInnodbVariables(): void
    {
        $checks = Requirements::checkMysql($this->mockConnection());

        $names = array_map(fn (Check $check) => $check->getName(), $checks);

        $this->assertNotContains('innodb_large_prefix = ON ', $names);
        $this->assertNotContains('innodb_file_format = Barracuda', $names);
    }

    public function testCheckMysqlStillReportsRemainingChecks(): void
    {
        $checks = Requirements::checkMysql($this->mockConnection());

        $byName = [];
        foreach ($checks as $check) {
            $byName[$check->getName()] = $check;
        }

        $this->assertArrayHasKey('InnoDB Support', $byName);
        $this->assertSame(Check::STATE_OK, $byName['InnoDB Support']->getState());

        $this->assertArrayHasKey('Database Charset utf8mb4', $byName);
        $this->assertSame(Check::STATE_OK, $byName['Database Charset utf8mb4']->getState());

        $this->assertArrayHasKey('innodb_file_per_table = ON', $byName);
        $this->assertSame(Check::STATE_OK, $byName['innodb_file_per_table = ON']->getState());
    }
}
