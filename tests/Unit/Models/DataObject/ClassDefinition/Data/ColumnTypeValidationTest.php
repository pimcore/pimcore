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

use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\DateRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-qfg9-rq74-hp35: columnType/queryColumnType of Date, Datetime
 * and DateRange fields are concatenated verbatim into ALTER TABLE DDL by
 * Helper\Dao::addModifyColumn(), so that value must be a plain SQL type expression. See
 * DaoAddModifyColumnValidationTest for the validator itself and its enforcement at that sink.
 *
 * The setters intentionally do NOT validate - the property is @internal but the setter is
 * public API, and validating on the setter would also reject already-persisted class
 * definitions when __set_state() replays them on class load, turning a legitimate but unusual
 * stored value into a silently missing class.
 */
class ColumnTypeValidationTest extends TestCase
{
    private const INJECTION_PAYLOAD = 'bigint(20), DROP COLUMN `oo_id`, ADD INDEX `pwn`(`o_published`) -- ';

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
