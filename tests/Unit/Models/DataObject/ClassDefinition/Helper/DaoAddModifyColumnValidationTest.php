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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Helper;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;

/**
 * Regression test for GHSA-qfg9-rq74-hp35: addModifyColumn() is the point where a field's
 * columnType/queryColumnType is concatenated verbatim into ALTER TABLE ... ADD/CHANGE COLUMN.
 * For fields whose type implements UserDefinedColumnTypeInterface (Date, Datetime, DateRange -
 * the only types with a public setColumnType()) it must reject anything that is not a plain SQL
 * type expression before that string ever reaches the database.
 *
 * The check must NOT apply to fields that don't implement that interface: their columnType is
 * computed internally and is not guaranteed to match the allowlist - e.g. BooleanSelect returns
 * the fixed, entirely safe 'tinyint(1) null', which the allowlist rejects (it only permits a
 * trailing 'unsigned'/'zerofill'). Validating unconditionally for every field type would make
 * every class containing a BooleanSelect field fail to create/update its database table.
 */
class DaoAddModifyColumnValidationTest extends TestCase
{
    private const INJECTION_PAYLOAD = 'bigint(20), DROP COLUMN `oo_id`, ADD INDEX `pwn`(`o_published`) -- ';

    /**
     * @dataProvider userDefinedColumnTypeFieldProvider
     */
    public function testRejectsInjectedTypeForUserDefinedColumnTypeField(Data $field): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->expects($this->never())->method('executeQuery');

        $this->expectException(InvalidArgumentException::class);

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callAddModifyColumn('object_store_test', 'mycolumn', self::INJECTION_PAYLOAD, '', 'NULL', $field);
    }

    public static function userDefinedColumnTypeFieldProvider(): array
    {
        return [
            Date::class => [new Date()],
            Datetime::class => [new Datetime()],
            DateRange::class => [new DateRange()],
        ];
    }

    public function testAddsColumnForLegitimateTypeOnUserDefinedColumnTypeField(): void
    {
        $mockDb = $this->createMock(Connection::class);

        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockDb->expects($this->once())
            ->method('executeQuery')
            ->with($this->callback(function (string $sql) {
                return str_contains($sql, 'ADD COLUMN')
                    && str_contains($sql, 'bigint(20)');
            }))
            ->willReturn($resultMock);

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callAddModifyColumn('object_store_test', 'mycolumn', 'bigint(20)', '', 'NULL', new Date());
    }

    public function testDoesNotValidateForFieldsWithoutUserDefinedColumnType(): void
    {
        $mockDb = $this->createMock(Connection::class);

        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockDb->expects($this->once())
            ->method('executeQuery')
            ->with($this->callback(fn (string $sql) => str_contains($sql, 'tinyint(1) null')))
            ->willReturn($resultMock);

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callAddModifyColumn('object_store_test', 'mycolumn', 'tinyint(1) null', '', 'NULL', new BooleanSelect());
    }

    /**
     * @dataProvider legitimateColumnTypeProvider
     */
    public function testValidateColumnTypeAcceptsLegitimateTypes(string $columnType): void
    {
        $this->createDaoWithDb($this->createMock(Connection::class))->callValidateColumnType($columnType);
        $this->addToAssertionCount(1);
    }

    public static function legitimateColumnTypeProvider(): array
    {
        return [
            'bigint with length' => ['bigint(20)'],
            'plain date' => ['date'],
            'plain datetime' => ['datetime'],
            'datetime with precision' => ['datetime(6)'],
            'decimal with precision and scale' => ['decimal(10,2)'],
            'unsigned modifier' => ['int unsigned'],
            'zerofill modifier' => ['int zerofill'],
            'unsigned and zerofill combined' => ['bigint(20) unsigned zerofill'],
        ];
    }

    public function testValidateColumnTypeRejectsInjectionPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createDaoWithDb($this->createMock(Connection::class))->callValidateColumnType(self::INJECTION_PAYLOAD);
    }

    /**
     * Creates a test double that exposes the trait's addModifyColumn()/validateColumnType() methods.
     */
    private function createDaoWithDb(Connection $db): object
    {
        return new class($db) {
            use \Pimcore\Model\DataObject\ClassDefinition\Helper\Dao {
                addModifyColumn as public callAddModifyColumn;
                validateColumnType as public callValidateColumnType;
            }

            protected \Doctrine\DBAL\Connection $db;

            protected array $tableDefinitions = [];

            public function __construct(Connection $db)
            {
                $this->db = $db;
            }

            // Stub required by the trait's other methods
            protected function getValidTableColumns(string $table, bool $cache = true): array
            {
                return [];
            }

            protected function resetValidTableColumnsCache(string $table): void
            {
            }
        };
    }
}
