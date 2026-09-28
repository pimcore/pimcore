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

namespace Pimcore\Tests\Unit\Model\DataObject\Objectbrick\Definition;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\Objectbrick\Definition\Dao;
use ReflectionClass;
use ReflectionMethod;

/**
 * Objectbrick\Definition\Dao::removeIndices() overrides (rather than reuses) the
 * ClassDefinition\Helper\Dao trait method it also `use`s, so it has its own raw-backtick-concatenation
 * DDL-injection sink (GHSA-2rmm-27mv-jwg5) that DaoRemoveUnusedColumnsTest's trait-only test double
 * never exercises. It is still reached via the shared trait's removeUnusedColumns() -> $this->removeIndices()
 * call on an Objectbrick Dao instance, so it needs its own direct coverage.
 */
class DaoRemoveIndicesTest extends TestCase
{
    private const MALICIOUS_KEY = 'x`, DROP COLUMN `oo_classname';

    public function testMaliciousIndexKeyIsQuotedInObjectbrickDropIndexStatement(): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->method('quoteIdentifier')->willReturnCallback(
            static fn (string $id): string => '`' . str_replace('`', '``', $id) . '`'
        );

        $executedQueries = [];
        $mockDb->method('executeQuery')->willReturnCallback(function (string $sql) use (&$executedQueries) {
            $executedQueries[] = $sql;

            return $this->createMock(Result::class);
        });

        $dao = (new ReflectionClass(Dao::class))->newInstanceWithoutConstructor();
        $dao->db = $mockDb;

        $removeIndices = new ReflectionMethod(Dao::class, 'removeIndices');
        $removeIndices->setAccessible(true);
        // 'object_brick_query_...' table name selects the 'p_index_' prefix branch.
        $removeIndices->invoke($dao, 'object_brick_query_test_1', [self::MALICIOUS_KEY], []);

        $this->assertCount(1, $executedQueries);
        // The malicious value must end up fully inside one quoted identifier - a raw, unescaped
        // backtick here would let it inject a second, executable "DROP COLUMN" clause.
        $this->assertSame(
            'ALTER TABLE `object_brick_query_test_1` DROP INDEX `p_index_x``, DROP COLUMN ``oo_classname`;',
            $executedQueries[0]
        );
    }
}
