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

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-mcm9-vcjc-jg59: Date, Datetime and DateRange do not strip
 * columnType/queryColumnType from (de)serialized class-definition data (unlike the base field
 * class), so an imported columnType used to be stored verbatim and later concatenated raw into
 * an "ALTER TABLE ... ADD COLUMN" DDL statement, allowing an attacker with class-definition
 * permission to inject arbitrary DDL clauses.
 */
class DateColumnTypeValidationTest extends TestCase
{
    public function testDateAcceptsLegitimateColumnTypes(): void
    {
        $field = new Date();

        $field->setColumnType('bigint(20)');
        $this->assertSame('bigint(20)', $field->getColumnType());

        $field->setColumnType('date');
        $this->assertSame('date', $field->getColumnType());

        $field->setColumnType('bigint(20) zerofill');
        $this->assertSame('bigint(20) zerofill', $field->getColumnType());

        $field->setColumnType('bigint(20) unsigned zerofill');
        $this->assertSame('bigint(20) unsigned zerofill', $field->getColumnType());

        foreach (['varchar(10)', 'decimal(10,2)', 'double'] as $legacyType) {
            $field->setColumnType($legacyType);
            $this->assertSame($legacyType, $field->getColumnType());
        }
    }

    public function testDateRejectsInjectedColumnType(): void
    {
        $field = new Date();

        $this->expectException(InvalidArgumentException::class);
        $field->setColumnType('bigint(20), ADD COLUMN `evil` varchar(10)');
    }

    public function testDatetimeAcceptsLegitimateColumnTypes(): void
    {
        $field = new Datetime();

        $field->setColumnType('bigint(20)');
        $this->assertSame('bigint(20)', $field->getColumnType());

        $field->setColumnType('datetime');
        $this->assertSame('datetime', $field->getColumnType());
    }

    public function testDatetimeRejectsInjectedColumnType(): void
    {
        $field = new Datetime();

        $this->expectException(InvalidArgumentException::class);
        $field->setColumnType('bigint(20), ADD COLUMN `evil` varchar(10)');
    }

    public function testDateRangeAcceptsLegitimateColumnType(): void
    {
        $field = new DateRange();

        $field->setColumnType('bigint(20)');
        $this->assertSame(
            ['start_date' => 'bigint(20)', 'end_date' => 'bigint(20)'],
            $field->getColumnType()
        );
    }

    public function testDateRangeRejectsInjectedColumnType(): void
    {
        $field = new DateRange();

        $this->expectException(InvalidArgumentException::class);
        $field->setColumnType('bigint(20), ADD COLUMN `evil` varchar(10)');
    }

    public function testDateRangeRejectsInjectedColumnTypeInArrayForm(): void
    {
        $field = new DateRange();

        $this->expectException(InvalidArgumentException::class);
        $field->setColumnType([
            'start_date' => 'bigint(20)',
            'end_date' => 'bigint(20), ADD COLUMN `evil` varchar(10)',
        ]);
    }

    /**
     * @dataProvider keywordOnlyClauseProvider
     */
    public function testRejectsKeywordOnlyClauses(string $columnType): void
    {
        foreach ([new Date(), new Datetime(), new DateRange()] as $field) {
            try {
                $field->setColumnType($columnType);
                $this->fail(sprintf('%s accepted "%s"', $field::class, $columnType));
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public static function keywordOnlyClauseProvider(): array
    {
        return [
            ['varchar(255) UNIQUE'],
            ['int NOT NULL'],
            ['bigint(20) PRIMARY KEY'],
            ['varchar(10) NOT NULL'],
            ['decimal(10,2) UNIQUE'],
            ['bigint(20) unsigned UNIQUE'],
        ];
    }
}
