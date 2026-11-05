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

use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The grid filter field name must always be passed through the identifier-quoting helper
 * (which doubles embedded backticks) instead of being wrapped in raw backticks.
 */
class DatetimeFilterConditionTest extends TestCase
{
    // these tests only quote values via the DB connection and don't mutate data, so cleanup can stay disabled
    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return true;
    }

    public function testLegitimateFieldNameOnDatetimeColumn(): void
    {
        $field = new Datetime();
        $field->setColumnType('datetime');

        $condition = $field->getFilterConditionExt(
            strtotime('2026-09-11'),
            '=',
            ['name' => 'myDate', 'brickPrefix' => '']
        );

        $this->assertSame("DATE(`myDate`) = '2026-09-11'", $condition);
    }

    public function testLegitimateFieldNameOnNonDatetimeColumn(): void
    {
        $field = new Datetime();
        $field->setColumnType('bigint(20)');

        $timestamp = strtotime('2026-09-11');
        $condition = $field->getFilterConditionExt($timestamp, '=', ['name' => 'myDate']);

        $this->assertStringStartsWith('`myDate` BETWEEN ', $condition);
    }

    public function testEmbeddedBacktickInFieldNameIsQuotedOnDatetimeColumn(): void
    {
        $field = new Datetime();
        $field->setColumnType('datetime');

        $condition = $field->getFilterConditionExt(
            strtotime('2026-09-11'),
            '=',
            ['name' => 'my`Date', 'brickPrefix' => '']
        );

        $this->assertSame("DATE(`my``Date`) = '2026-09-11'", $condition);
    }

    public function testEmbeddedBacktickInFieldNameIsQuotedOnNonDatetimeColumn(): void
    {
        $field = new Datetime();
        $field->setColumnType('bigint(20)');

        $timestamp = strtotime('2026-09-11');
        $condition = $field->getFilterConditionExt($timestamp, '=', ['name' => 'my`Date']);

        $this->assertSame(
            "`my``Date` BETWEEN '" . $timestamp . "' AND '" . ($timestamp + 86399) . "'",
            $condition
        );
    }
}
