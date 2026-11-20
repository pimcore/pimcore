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
    private function mockConnection(string $serverVersion = '11.8.2-MariaDB'): Connection
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchFirstColumn')->willReturn(['InnoDB']);
        $connection->method('fetchOne')->willReturn($serverVersion);

        $connection->method('fetchAssociative')->willReturnCallback(
            function (string $query) {
                // Requirements.php uses SQL LIKE escape sequences ("character\_set\_database"),
                // so match against the un-escaped query text instead of the raw string.
                $normalizedQuery = str_replace('\\_', '_', $query);

                if (str_contains($normalizedQuery, 'character_set_database')) {
                    return ['Value' => 'utf8mb4'];
                }

                if (str_contains($normalizedQuery, 'innodb_file_per_table')) {
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

    /**
     * @dataProvider serverVersionProvider
     */
    public function testCheckMysqlServerVersion(string $serverVersion, int $expectedState): void
    {
        $checks = Requirements::checkMysql($this->mockConnection($serverVersion));

        $versionChecks = array_values(array_filter(
            $checks,
            fn (Check $check) => str_starts_with($check->getName(), 'Database Server Version')
        ));

        $this->assertCount(1, $versionChecks);
        $this->assertSame($expectedState, $versionChecks[0]->getState(), $serverVersion);
    }

    public static function serverVersionProvider(): array
    {
        return [
            // supported: at or above the minimum (MariaDB 10.11 / MySQL 8.4)
            'MariaDB minimum' => ['10.11.0-MariaDB', Check::STATE_OK],
            'MariaDB 10.11 patch' => ['10.11.14-MariaDB-ubu2204', Check::STATE_OK],
            'MariaDB 11.4 LTS' => ['11.4.5-MariaDB', Check::STATE_OK],
            'MariaDB 11.8 LTS' => ['11.8.2-MariaDB', Check::STATE_OK],
            'MariaDB 12.3 LTS' => ['12.3.1-MariaDB', Check::STATE_OK],
            'MariaDB with replication prefix' => ['5.5.5-10.11.6-MariaDB', Check::STATE_OK],
            'MySQL minimum' => ['8.4.0', Check::STATE_OK],
            'MySQL 8.4 patch' => ['8.4.4', Check::STATE_OK],
            'MySQL 9.7 LTS' => ['9.7.0', Check::STATE_OK],
            'MySQL 9.7 with suffix' => ['9.7.1-commercial', Check::STATE_OK],
            'MySQL calendar version' => ['26.7.0', Check::STATE_OK],
            'Percona Server' => ['8.4.3-3-percona', Check::STATE_OK],
            'Aurora MySQL 8.4' => ['8.4.7', Check::STATE_OK],

            // below the minimum: a warning, never an error
            'MariaDB 10.6' => ['10.6.22-MariaDB', Check::STATE_WARNING],
            'MariaDB 10.5' => ['10.5.29-MariaDB', Check::STATE_WARNING],
            'MariaDB 10.3' => ['10.3.39-MariaDB', Check::STATE_WARNING],
            'MariaDB 10.10 (not 10.11)' => ['10.10.7-MariaDB', Check::STATE_WARNING],
            'MariaDB 10.6 with replication prefix' => ['5.5.5-10.6.1-MariaDB', Check::STATE_WARNING],
            'MySQL 8.3' => ['8.3.0', Check::STATE_WARNING],
            'MySQL 8.0' => ['8.0.45', Check::STATE_WARNING],
            'Aurora MySQL 3 (MySQL 8.0 compatible)' => ['8.0.mysql_aurora.3.10.1', Check::STATE_WARNING],
            'MySQL 5.7' => ['5.7.44', Check::STATE_WARNING],

            // not parseable: warn, do not fail
            'empty' => ['', Check::STATE_WARNING],
            'garbage' => ['not-a-version', Check::STATE_WARNING],
        ];
    }

    public function testCheckMysqlServerVersionMessageNamesVersionAndMinimum(): void
    {
        $checks = Requirements::checkMysql($this->mockConnection('10.6.22-MariaDB'));

        foreach ($checks as $check) {
            if (str_starts_with($check->getName(), 'Database Server Version')) {
                $this->assertStringContainsString('10.6.22', $check->getMessage());
                $this->assertStringContainsString('10.11', $check->getMessage());

                return;
            }
        }

        $this->fail('No server version check was reported.');
    }
}
