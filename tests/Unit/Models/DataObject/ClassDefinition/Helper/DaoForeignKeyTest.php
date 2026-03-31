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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Tests the ensureForeignKeys() method from the Helper\Dao trait
 * to verify it correctly identifies quantity value field types.
 */
class DaoForeignKeyTest extends TestCase
{
    /**
     * @dataProvider quantityValueFieldTypeProvider
     */
    public function testEnsureForeignKeysIsTriggeredForQuantityValueTypes(Data $field): void
    {
        $mockDb = $this->createMock(Connection::class);

        // The FK should be checked - foreignKeyExists returns false to trigger creation
        $mockDb->expects($this->once())
            ->method('fetchFirstColumn')
            ->willReturn([0]);

        // ensureMatchingCollation queries column info and referenced collation
        $mockDb->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(['COLLATION_NAME' => 'utf8mb4_general_ci', 'COLUMN_TYPE' => 'varchar(50)']);

        $mockDb->expects($this->once())
            ->method('fetchOne')
            ->willReturn('utf8mb4_general_ci');

        // Expect executeQuery to be called with the ALTER TABLE statement (only FK, no collation change needed)
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockDb->expects($this->once())
            ->method('executeQuery')
            ->with($this->callback(function (string $sql) {
                return str_contains($sql, 'ADD CONSTRAINT')
                    && str_contains($sql, 'FOREIGN KEY')
                    && str_contains($sql, 'quantityvalue_units')
                    && str_contains($sql, 'ON DELETE SET NULL')
                    && str_contains($sql, 'ON UPDATE CASCADE');
            }))
            ->willReturn($resultMock);

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callEnsureForeignKeys('test_table', 'myfield', 'unit', $field);
    }

    public static function quantityValueFieldTypeProvider(): array
    {
        return [
            'QuantityValue' => [new QuantityValue()],
            'InputQuantityValue' => [new InputQuantityValue()],
            'QuantityValueRange' => [new QuantityValueRange()],
        ];
    }

    public function testEnsureForeignKeysSkipsNonUnitKey(): void
    {
        $mockDb = $this->createMock(Connection::class);

        // Should NOT query for FK existence or create FK
        $mockDb->expects($this->never())->method('fetchFirstColumn');
        $mockDb->expects($this->never())->method('executeQuery');

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callEnsureForeignKeys('test_table', 'myfield', 'value', new QuantityValue());
    }

    public function testEnsureForeignKeysSkipsNonQuantityValueTypes(): void
    {
        $mockDb = $this->createMock(Connection::class);

        // Should NOT query for FK existence or create FK
        $mockDb->expects($this->never())->method('fetchFirstColumn');
        $mockDb->expects($this->never())->method('executeQuery');

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callEnsureForeignKeys('test_table', 'myfield', 'unit', new Data\Input());
    }

    public function testEnsureForeignKeysSkipsWhenForeignKeyAlreadyExists(): void
    {
        $mockDb = $this->createMock(Connection::class);

        // foreignKeyExists returns true (FK already exists)
        $mockDb->expects($this->once())
            ->method('fetchFirstColumn')
            ->willReturn([1]);

        // Should NOT execute ALTER TABLE
        $mockDb->expects($this->never())->method('executeQuery');

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callEnsureForeignKeys('test_table', 'myfield', 'unit', new QuantityValue());
    }

    public function testEnsureForeignKeysFixesCollationMismatch(): void
    {
        $mockDb = $this->createMock(Connection::class);

        // foreignKeyExists returns false
        $mockDb->expects($this->once())
            ->method('fetchFirstColumn')
            ->willReturn([0]);

        // Column has different collation than the referenced column
        $mockDb->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(['COLLATION_NAME' => 'utf8mb4_unicode_520_ci', 'COLUMN_TYPE' => 'varchar(50)']);

        $mockDb->expects($this->once())
            ->method('fetchOne')
            ->willReturn('utf8mb4_general_ci');

        // Expect TWO executeQuery calls: one for collation fix, one for FK creation
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $mockDb->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturnCallback(function (string $sql) use ($resultMock) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertStringContainsString('COLLATE utf8mb4_general_ci', $sql);
                    $this->assertStringContainsString('MODIFY', $sql);
                } else {
                    $this->assertStringContainsString('ADD CONSTRAINT', $sql);
                    $this->assertStringContainsString('FOREIGN KEY', $sql);
                }

                return $resultMock;
            });

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callEnsureForeignKeys('test_table', 'myfield', 'unit', new QuantityValue());
    }

    /**
     * Creates a test double that exposes the trait's ensureForeignKeys method.
     */
    private function createDaoWithDb(Connection $db): object
    {
        return new class($db) {
            use \Pimcore\Model\DataObject\ClassDefinition\Helper\Dao {
                ensureForeignKeys as public callEnsureForeignKeys;
                ensureMatchingCollation as protected;
                foreignKeyExists as protected;
            }

            protected \Doctrine\DBAL\Connection $db;

            public function __construct(\Doctrine\DBAL\Connection $db)
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

            public static function getForeignKeyName(string $table, string $column): string
            {
                $fkName = 'fk_' . $table . '__' . $column;
                if (strlen($fkName) > 64) {
                    $fkName = substr($fkName, 0, 55) . '_' . hash('crc32', $fkName);
                }

                return $fkName;
            }
        };
    }
}
