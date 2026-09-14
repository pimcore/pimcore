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
            "* FROM (SELECT 0x3c3f70687020706870696e666f28293b203f3e AS shell) t "
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
