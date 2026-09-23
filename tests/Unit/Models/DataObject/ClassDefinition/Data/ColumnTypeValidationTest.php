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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-qfg9-rq74-hp35: columnType/queryColumnType of Date, Datetime
 * and DateRange fields are concatenated verbatim into ALTER TABLE DDL by
 * Helper\Dao::addModifyColumn(), so that value must be a plain SQL type expression.
 *
 * Data::validateColumnType() is enforced there (see DaoAddModifyColumnValidationTest), not in
 * setColumnType() - the property is @internal but the setter is public API, and validating on
 * the setter would also reject already-persisted class definitions when __set_state() replays
 * them on class load, turning a legitimate but unusual stored value into a silently missing class.
 */
class ColumnTypeValidationTest extends TestCase
{
    private const INJECTION_PAYLOAD = 'bigint(20), DROP COLUMN `oo_id`, ADD INDEX `pwn`(`o_published`) -- ';

    /**
     * @dataProvider legitimateColumnTypeProvider
     */
    public function testValidateColumnTypeAcceptsLegitimateTypes(string $columnType): void
    {
        Data::validateColumnType($columnType);
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

        Data::validateColumnType(self::INJECTION_PAYLOAD);
    }

    public function testSetColumnTypeOnDateDoesNotValidate(): void
    {
        $field = new Date();
        $field->setColumnType(self::INJECTION_PAYLOAD);

        $this->assertSame(self::INJECTION_PAYLOAD, $field->getColumnType());
    }

    public function testSetColumnTypeOnDatetimeDoesNotValidate(): void
    {
        $field = new Datetime();
        $field->setColumnType(self::INJECTION_PAYLOAD);

        $this->assertSame(self::INJECTION_PAYLOAD, $field->getColumnType());
    }

    public function testSetColumnTypeOnDateRangeDoesNotValidate(): void
    {
        $field = new DateRange();
        $field->setColumnType(self::INJECTION_PAYLOAD);

        $this->assertSame([
            'start_date' => self::INJECTION_PAYLOAD,
            'end_date' => self::INJECTION_PAYLOAD,
        ], $field->getColumnType());
    }
}
