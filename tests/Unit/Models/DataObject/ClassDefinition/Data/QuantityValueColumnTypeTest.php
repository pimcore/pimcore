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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Tests that QuantityValue, InputQuantityValue and QuantityValueRange data types
 * all define their __unit column as varchar(50) to match the quantityvalue_units.id
 * column type, which is required for foreign key constraints.
 */
class QuantityValueColumnTypeTest extends TestCase
{
    private const EXPECTED_UNIT_COLUMN_TYPE = 'varchar(50)';

    public function testQuantityValueUnitColumnTypeMatchesUnitTable(): void
    {
        $field = new QuantityValue();

        $columnType = $field->getColumnType();
        $this->assertIsArray($columnType);
        $this->assertArrayHasKey('unit', $columnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testQuantityValueQueryColumnTypeMatchesUnitTable(): void
    {
        $field = new QuantityValue();

        $queryColumnType = $field->getQueryColumnType();
        $this->assertIsArray($queryColumnType);
        $this->assertArrayHasKey('unit', $queryColumnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $queryColumnType['unit']);
    }

    public function testQuantityValueIntegerModeUnitColumnType(): void
    {
        $field = new QuantityValue();
        $field->setInteger(true);

        $columnType = $field->getColumnType();
        $this->assertSame('bigint(20)', $columnType['value']);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testQuantityValueDecimalModeUnitColumnType(): void
    {
        $field = new QuantityValue();
        $field->setDecimalSize(10);
        $field->setDecimalPrecision(2);

        $columnType = $field->getColumnType();
        $this->assertSame('DECIMAL(10, 2)', $columnType['value']);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testQuantityValueDefaultModeUnitColumnType(): void
    {
        $field = new QuantityValue();

        $columnType = $field->getColumnType();
        $this->assertSame('double', $columnType['value']);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testQuantityValueRangeUnitColumnTypeMatchesUnitTable(): void
    {
        $field = new QuantityValueRange();

        $columnType = $field->getColumnType();
        $this->assertIsArray($columnType);
        $this->assertArrayHasKey('unit', $columnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testQuantityValueRangeQueryColumnTypeMatchesUnitTable(): void
    {
        $field = new QuantityValueRange();

        $queryColumnType = $field->getQueryColumnType();
        $this->assertIsArray($queryColumnType);
        $this->assertArrayHasKey('unit', $queryColumnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $queryColumnType['unit']);
    }

    public function testQuantityValueRangeColumnTypeHasAllKeys(): void
    {
        $field = new QuantityValueRange();

        $columnType = $field->getColumnType();
        $this->assertArrayHasKey('minimum', $columnType);
        $this->assertArrayHasKey('maximum', $columnType);
        $this->assertArrayHasKey('unit', $columnType);
        $this->assertSame('double', $columnType['minimum']);
        $this->assertSame('double', $columnType['maximum']);
    }

    public function testInputQuantityValueUnitColumnTypeMatchesUnitTable(): void
    {
        $field = new InputQuantityValue();

        $columnType = $field->getColumnType();
        $this->assertIsArray($columnType);
        $this->assertArrayHasKey('unit', $columnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $columnType['unit']);
    }

    public function testInputQuantityValueQueryColumnTypeMatchesUnitTable(): void
    {
        $field = new InputQuantityValue();

        $queryColumnType = $field->getQueryColumnType();
        $this->assertIsArray($queryColumnType);
        $this->assertArrayHasKey('unit', $queryColumnType);
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $queryColumnType['unit']);
    }

    /**
     * All three quantity value types must use the same unit column type
     * to ensure consistent foreign key constraints.
     */
    public function testAllQuantityValueTypesHaveConsistentUnitColumnType(): void
    {
        $quantityValue = new QuantityValue();
        $inputQuantityValue = new InputQuantityValue();
        $quantityValueRange = new QuantityValueRange();

        $qvUnit = $quantityValue->getColumnType()['unit'];
        $iqvUnit = $inputQuantityValue->getColumnType()['unit'];
        $qvrUnit = $quantityValueRange->getColumnType()['unit'];

        $this->assertSame($qvUnit, $iqvUnit, 'QuantityValue and InputQuantityValue must use the same unit column type');
        $this->assertSame($qvUnit, $qvrUnit, 'QuantityValue and QuantityValueRange must use the same unit column type');
        $this->assertSame(self::EXPECTED_UNIT_COLUMN_TYPE, $qvUnit);
    }
}
