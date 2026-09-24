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

namespace Pimcore\Tests\Unit\CustomReportsBundle\Tool\Adapter;

use InvalidArgumentException;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\Sql;
use Pimcore\Tests\Support\Test\TestCase;
use stdClass;

final class SqlTest extends TestCase
{
    /**
     * Builds an adapter instance with buildQueryString() exposed publicly, so the
     * per-fragment validation can be exercised without needing a real DB connection
     * (buildQueryString() only touches Db::get() when drillDownFilters/selectField
     * are passed, which none of these tests do).
     */
    private function adapter(): Sql
    {
        return new class(new stdClass()) extends Sql {
            public function exposedBuildQueryString(stdClass $config): string
            {
                return $this->buildQueryString($config);
            }
        };
    }

    /**
     * An adapter whose DB round-trip is stubbed, so getColumns() can be exercised end-to-end
     * (fragment validation + derived-table wrapping) without a real DB connection. Captures
     * the exact, already-wrapped SQL that would have been sent to Db::get()->fetchAssociative().
     *
     * @param-out string $capturedSql
     */
    private function adapterCapturingWrappedSql(stdClass $config, ?string &$capturedSql): Sql
    {
        return new class($config, $capturedSql) extends Sql {
            private ?string $capturedSql;

            public function __construct(stdClass $config, ?string &$capturedSql)
            {
                parent::__construct($config);
                $this->capturedSql = &$capturedSql;
            }

            protected function fetchAssociative(string $sql): array|false
            {
                $this->capturedSql = $sql;

                return ['id' => 1, 'name' => 'foo'];
            }
        };
    }

    private function configWith(string $key, string $value): stdClass
    {
        $config = new stdClass();
        $config->$key = $value;

        return $config;
    }

    public function testRejectsIntoOutfilePayloadEndingInBareComment(): void
    {
        // GHSA-rw66-cxfw-89c6 PoC: a bare trailing "--" (no following whitespace) used to
        // slip past both the old '/--\s/' rule and the whole-query DDL/DML regex, letting
        // an INTO OUTFILE statement reach Db::get()->fetchAssociative() in getColumns().
        $config = $this->configWith(
            'sql',
            '* FROM (SELECT 0x3c3f70687020706870696e666f28293b203f3e AS shell) t '
            . "INTO OUTFILE '/var/www/html/public/poc_shell.php'--"
        );

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testRejectsIntoDumpfile(): void
    {
        $config = $this->configWith('where', "1=1 INTO DUMPFILE '/tmp/x'");

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testRejectsLoadFile(): void
    {
        $config = $this->configWith('sql', "LOAD_FILE('/etc/passwd') AS secret");

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testRejectsFragmentTerminalCommentWithoutTrailingWhitespace(): void
    {
        // Even without a file primitive, a fragment ending in a bare "--" must be
        // rejected: the appended ' LIMIT 0,1' would otherwise arm it as a comment
        // at execution time and truncate the intended query.
        $config = $this->configWith('where', 'id = 1--');

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testRejectsLoadFileWithNewlineBeforeParen(): void
    {
        // The LOAD_FILE pattern's '\s*' between the function name and '(' matches a literal
        // newline (PCRE's '\s' does so without needing the 's' modifier); assert that
        // explicitly so a later refactor to '[ ]*' does not quietly reopen this bypass.
        $config = $this->configWith('sql', "LOAD_FILE\n('/etc/passwd') AS secret");

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testRejectsCommentStartFollowedByControlByte(): void
    {
        // MySQL's lexer starts a "--" comment when followed by a space OR any control
        // character (my_iscntrl), not only the bytes PCRE's '\s' matches. A payload using
        // e.g. \x01 right after "--" used to slip past the whitespace-only rule. No other
        // forbidden primitive appears here, so this only exercises the comment-start rule.
        $config = $this->configWith('where', "id = 1--\x01harmless");

        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->exposedBuildQueryString($config);
    }

    public function testGetColumnsSendsComposedQueryAsDerivedTable(): void
    {
        $config = new stdClass();
        $config->sql = 'SELECT id, name';
        $config->from = 'my_table';

        $capturedSql = null;
        $adapter = $this->adapterCapturingWrappedSql($config, $capturedSql);

        $columns = $adapter->getColumns($config);

        $this->assertSame(['id', 'name'], $columns);
        $this->assertNotNull($capturedSql);
        $this->assertMatchesRegularExpression(
            '/^SELECT \* FROM \(.*my_table.*\) AS somerandxyz LIMIT 0,1$/s',
            $capturedSql
        );
    }

    public function testLegitimateFragmentsAreStillAccepted(): void
    {
        $config = new stdClass();
        $config->sql = 'SELECT COUNT(*) AS cnt';
        $config->from = 'my_table';
        $config->where = 'cnt > 0';
        $config->groupby = 'cnt';

        $sql = $this->adapter()->exposedBuildQueryString($config);

        $this->assertStringContainsString('SELECT COUNT(*) AS cnt', $sql);
        $this->assertStringContainsString('FROM', $sql);
        $this->assertStringContainsString('my_table', $sql);
        $this->assertStringContainsString('WHERE (cnt > 0)', $sql);
        $this->assertStringContainsString('GROUP BY', $sql);
    }

    public function testColumnNameResemblingForbiddenFunctionIsNotAFalsePositive(): void
    {
        // "load_file_id" must not be confused with the LOAD_FILE(...) function call.
        $config = $this->configWith('sql', 'load_file_id, name');

        $sql = $this->adapter()->exposedBuildQueryString($config);

        $this->assertStringContainsString('load_file_id', $sql);
    }
}
