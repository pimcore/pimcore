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
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * Covers the table/column deny-list enforced on the sql/from/where/groupby fragments of a Custom
 * Report SQL data source (text-based, via buildQueryString()), and the resolved-column check that
 * validates a query's actual result-set columns against the same deny-list.
 */
class SqlTest extends TestCase
{
    protected function needsDb(): bool
    {
        return true;
    }

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

    private function identifierBoundaryPattern(string $name): string
    {
        $adapter = new Sql((object) []);
        $method = new ReflectionMethod(Sql::class, 'identifierBoundaryPattern');
        $method->setAccessible(true);

        return $method->invoke($adapter, $name);
    }

    public function testIdentifierBoundaryPatternMatchesNamesWithNonWordEdgeCharacters(): void
    {
        // "\b" relies on \w (ASCII word chars) and silently fails to anchor around identifiers that
        // start/end with characters outside that set - e.g. a denied name of "$private$" would never
        // match "... FROM $private$" via \b, since neither "$" nor the preceding space is a \w char.
        $pattern = $this->identifierBoundaryPattern('$private$');

        $this->assertSame(1, preg_match($pattern, 'name FROM $private$'));
        // Must not match a longer identifier that merely contains the denied name as a prefix.
        $this->assertSame(0, preg_match($pattern, 'name FROM $private$table'));
    }

    public function testIdentifierBoundaryPatternMatchesUnicodeNames(): void
    {
        $pattern = $this->identifierBoundaryPattern('über');

        $this->assertSame(1, preg_match($pattern, 'SELECT über FROM t'));
        $this->assertSame(0, preg_match($pattern, 'SELECT übertragung FROM t'));
    }

    private function assertResolvedColumnsAllowed(array $columnNames): void
    {
        $adapter = new Sql((object) []);
        $method = new ReflectionMethod(Sql::class, 'assertResolvedColumnsAllowed');
        $method->setAccessible(true);
        $method->invoke($adapter, $columnNames);
    }

    public function testResolvedDeniedColumnIsRejected(): void
    {
        // This is what a wildcard/DISTINCT/UNION/subquery projection would actually resolve to at
        // execution time - the deny-list is enforced against the real result-set columns rather than
        // guessed from the query text, so it doesn't matter how the column ended up in the result.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "password"/i');

        $this->assertResolvedColumnsAllowed(['id', 'name', 'password']);
    }

    public function testResolvedDeniedColumnIsRejectedCaseInsensitively(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/column "PASSWORD"/i');

        $this->assertResolvedColumnsAllowed(['id', 'PASSWORD']);
    }

    public function testResolvedAllowedColumnsAreNotRejected(): void
    {
        $this->assertResolvedColumnsAllowed(['id', 'name', 'email']);
        $this->addToAssertionCount(1);
    }

    public function testGetColumnsWorksWhenTheFragmentAlreadyEndsInLimit(): void
    {
        // The "sql" fragment may be an entire freeform statement, used as-is once it already starts
        // with "SELECT" - sampling its result columns by appending another "LIMIT 0,1" directly would
        // previously break with a SQL syntax error if it already ends in its own LIMIT clause.
        $db = Db::get();
        $db->executeStatement('CREATE TEMPORARY TABLE sql_adapter_test_limit (id INT, name VARCHAR(50))');

        try {
            $db->executeStatement("INSERT INTO sql_adapter_test_limit (id, name) VALUES (1, 'a'), (2, 'b')");

            $adapter = new Sql((object) []);
            $columns = $adapter->getColumns((object) [
                'sql' => 'SELECT id, name FROM sql_adapter_test_limit LIMIT 1',
            ]);

            $this->assertSame(['id', 'name'], $columns);
        } finally {
            $db->executeStatement('DROP TEMPORARY TABLE IF EXISTS sql_adapter_test_limit');
        }
    }

    public function testDeniedColumnViaWildcardIsDetectedEvenWhenNoRowsMatch(): void
    {
        // The deny-list check must not depend on the sample query actually matching rows - a
        // non-deterministic predicate or a concurrent data change could otherwise make the sample
        // empty while the real query returns rows (and the denied column) later. The table here has
        // zero rows, and "password" never appears in the report configuration text (only "from" is
        // set), so this can only be caught via the resolved result columns, not by fetching a sample
        // row or by matching the configured fragment text.
        $db = Db::get();
        $db->executeStatement('CREATE TEMPORARY TABLE sql_adapter_test_empty (id INT, password VARCHAR(50))');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/column "password"/i');

            $adapter = new Sql((object) []);
            $adapter->getColumns((object) [
                'from' => 'sql_adapter_test_empty',
            ]);
        } finally {
            $db->executeStatement('DROP TEMPORARY TABLE IF EXISTS sql_adapter_test_empty');
        }
    }
}
