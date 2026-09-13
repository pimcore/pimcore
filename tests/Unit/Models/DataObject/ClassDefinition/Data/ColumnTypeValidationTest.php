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
 * Regression test for GHSA-qfg9-rq74-hp35: columnType/queryColumnType of Date, Datetime
 * and DateRange fields are concatenated verbatim into ALTER TABLE DDL, so setColumnType()
 * must reject anything that is not a plain SQL type expression.
 */
class ColumnTypeValidationTest extends TestCase
{
    private const INJECTION_PAYLOAD = 'bigint(20), DROP COLUMN `oo_id`, ADD INDEX `pwn`(`o_published`) -- ';

    public function testDateRejectsInjectedColumnType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Date())->setColumnType(self::INJECTION_PAYLOAD);
    }

    public function testDateAcceptsLegitimateColumnTypes(): void
    {
        $field = new Date();

        $field->setColumnType('bigint(20)');
        $this->assertSame('bigint(20)', $field->getColumnType());

        $field->setColumnType('date');
        $this->assertSame('date', $field->getColumnType());
    }

    public function testDatetimeRejectsInjectedColumnType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Datetime())->setColumnType(self::INJECTION_PAYLOAD);
    }

    public function testDatetimeAcceptsLegitimateColumnTypes(): void
    {
        $field = new Datetime();

        $field->setColumnType('bigint(20)');
        $this->assertSame('bigint(20)', $field->getColumnType());

        $field->setColumnType('datetime');
        $this->assertSame('datetime', $field->getColumnType());
    }

    public function testDateRangeRejectsInjectedScalarColumnType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DateRange())->setColumnType(self::INJECTION_PAYLOAD);
    }

    public function testDateRangeRejectsInjectedArrayColumnType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DateRange())->setColumnType([
            'start_date' => 'bigint(20)',
            'end_date' => self::INJECTION_PAYLOAD,
        ]);
    }

    public function testDateRangeAcceptsLegitimateColumnTypes(): void
    {
        $field = new DateRange();

        $field->setColumnType('bigint(20)');
        $this->assertSame([
            'start_date' => 'bigint(20)',
            'end_date' => 'bigint(20)',
        ], $field->getColumnType());

        $field->setColumnType([
            'start_date' => 'date',
            'end_date' => 'datetime',
        ]);
        $this->assertSame([
            'start_date' => 'date',
            'end_date' => 'datetime',
        ], $field->getColumnType());
    }
}
