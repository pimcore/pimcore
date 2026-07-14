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

namespace Pimcore\Tests\Unit\Bundle\CustomReportsBundle\Tool\Adapter;

use InvalidArgumentException;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\Sql;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * Covers the table/column deny-list enforced on the sql/from/where/groupby fragments of a
 * Custom Report SQL data source.
 */
class SqlTest extends TestCase
{
    private function buildQueryString(array $config): string
    {
        $adapter = new Sql((object) $config);
        $method = new ReflectionMethod(Sql::class, 'buildQueryString');
        $method->setAccessible(true);

        return $method->invoke($adapter, (object) $config);
    }

    public function testDeniedTableInFromFragmentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/table "users"/i');

        $this->buildQueryString([
            'sql' => 'name',
            'from' => 'pimcore.users',
        ]);
    }

    public function testDeniedColumnsInSqlFragmentAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "password"/i');

        $this->buildQueryString([
            'sql' => 'name,password,passwordRecoveryToken',
            'from' => 'pimcore.users',
        ]);
    }

    public function testDeniedTableHiddenBehindBackticksIsStillRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/table "users"/i');

        $this->buildQueryString([
            'sql' => 'name',
            'from' => '`users`',
        ]);
    }

    public function testDeniedNameHiddenInSubqueryIsRejected(): void
    {
        // The denied name is smuggled inside the "sql" (column list) fragment via a subquery,
        // rather than appearing directly in "from" - must still be caught.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(table "users"|column "password")/i');

        $this->buildQueryString([
            'sql' => '(SELECT password FROM users LIMIT 1) AS leaked',
            'from' => 'orders',
        ]);
    }

    public function testLegitimateReportIsNotRejected(): void
    {
        $sql = $this->buildQueryString([
            'sql' => 'name, email',
            'from' => 'my_products p',
        ]);

        $this->assertStringContainsString('name, email', $sql);
        $this->assertStringContainsString('my_products p', $sql);
    }

    public function testColumnNameSubstringIsNotFalselyRejected(): void
    {
        // "password_hash_something" must not trigger the "password" deny entry - word-boundary match only.
        $sql = $this->buildQueryString([
            'sql' => 'password_hash_something',
            'from' => 'userscount_table',
        ]);

        $this->assertStringContainsString('password_hash_something', $sql);
        $this->assertStringContainsString('userscount_table', $sql);
    }

    public function testDeniedNameInsideStringLiteralIsNotFalselyRejected(): void
    {
        // A literal comparison value merely containing the word "password" is not a column/table
        // reference and must not be rejected.
        $sql = $this->buildQueryString([
            'sql' => 'name',
            'from' => 'my_products',
            'where' => "name = 'password'",
        ]);

        $this->assertStringContainsString("'password'", $sql);
    }

    public function testWildcardSqlFragmentIsRejected(): void
    {
        // "*" expands to every column of the queried table at execution time, which would silently
        // include any denied column without it ever appearing in the "sql" fragment text.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/wildcard/i');

        $this->buildQueryString([
            'sql' => '*',
            'from' => 'orders',
        ]);
    }

    public function testEmptySqlFragmentIsRejected(): void
    {
        // An omitted "sql" fragment falls back to an implicit "SELECT *" - same risk as an explicit "*".
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/wildcard/i');

        $this->buildQueryString([
            'from' => 'orders',
        ]);
    }

    public function testTableQualifiedWildcardIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/wildcard/i');

        $this->buildQueryString([
            'sql' => 'name, o.*',
            'from' => 'orders o',
        ]);
    }

    public function testCountWildcardIsNotFalselyRejected(): void
    {
        // COUNT(*) is a legitimate aggregate, not a column-list wildcard, and must not be rejected.
        $sql = $this->buildQueryString([
            'sql' => 'name, COUNT(*) as cnt',
            'from' => 'orders',
        ]);

        $this->assertStringContainsString('COUNT(*)', $sql);
    }
}
