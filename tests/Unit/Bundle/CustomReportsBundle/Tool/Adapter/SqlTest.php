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
 * Covers the table/column deny-list enforced on the sql/from/where/groupby fragments of a Custom
 * Report SQL data source (text-based, via buildQueryString()), and the resolved-column check that
 * validates a query's actual result-set columns against the same deny-list.
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

    public function testBackslashEscapedQuoteCannotHideADeniedColumnReference(): void
    {
        // Two backslashes right before the closing quote is one *escaped backslash* in MySQL's own
        // parsing, which closes the string literal immediately after - "password" here is a live,
        // executing column reference, not string content. A quote-stripping regex that doesn't pair
        // up backslashes the same way can misjudge where the literal actually closes and blank out
        // (hide) the reference instead of flagging it.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "password"/i');

        $this->buildQueryString([
            'sql' => 'name',
            'from' => 'my_products',
            'where' => "name = '\\\\' OR password = 'x'",
        ]);
    }

    public function testEscapedQuoteInsideALiteralIsNotFalselyRejected(): void
    {
        // A backslash-escaped quote inside a literal must stay part of that (blanked-out) literal
        // rather than being misread as closing the string early.
        $sql = $this->buildQueryString([
            'sql' => 'name',
            'from' => 'my_products',
            'where' => "name = 'it\\'s a test'",
        ]);

        $this->assertStringContainsString("it\\'s a test", $sql);
    }

    private function assertResolvedColumnsAllowed(array $row): void
    {
        $adapter = new Sql((object) []);
        $method = new ReflectionMethod(Sql::class, 'assertResolvedColumnsAllowed');
        $method->setAccessible(true);
        $method->invoke($adapter, $row);
    }

    public function testResolvedDeniedColumnIsRejected(): void
    {
        // This is what a wildcard/DISTINCT/UNION/subquery projection would actually resolve to at
        // execution time - the deny-list is enforced against the real result-set columns rather than
        // guessed from the query text, so it doesn't matter how the column ended up in the result.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "password"/i');

        $this->assertResolvedColumnsAllowed(['id' => 1, 'name' => 'x', 'password' => 'hash']);
    }

    public function testResolvedDeniedColumnIsRejectedCaseInsensitively(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "PASSWORD"/i');

        $this->assertResolvedColumnsAllowed(['id' => 1, 'PASSWORD' => 'hash']);
    }

    public function testResolvedAllowedColumnsAreNotRejected(): void
    {
        $this->assertResolvedColumnsAllowed(['id' => 1, 'name' => 'x', 'email' => 'a@b.com']);
        $this->addToAssertionCount(1);
    }
}
