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
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-f7qw-2c4g-mh4h: the grid filter field name must be
 * passed through the identifier-quoting helper instead of being wrapped in
 * raw backticks, which allowed it to break out of the identifier context.
 */
class DateFilterConditionTest extends TestCase
{
    // these tests only quote values via the DB connection and don't mutate data, so cleanup can stay disabled
    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return true;
    }

    public function testLegitimateFieldNameOnDateColumn(): void
    {
        $field = new Date();
        $field->setColumnType('date');

        $condition = $field->getFilterConditionExt(
            strtotime('2026-09-11'),
            '=',
            ['name' => 'myDate']
        );

        $this->assertSame("`myDate` = '2026-09-11'", $condition);
    }

    public function testLegitimateFieldNameOnNonDateColumn(): void
    {
        $field = new Date();
        $field->setColumnType('bigint(20)');

        $timestamp = strtotime('2026-09-11');
        $condition = $field->getFilterConditionExt($timestamp, '=', ['name' => 'myDate']);

        $this->assertStringStartsWith('`myDate` BETWEEN ', $condition);
    }

    public function testBacktickPayloadInFieldNameIsNeutralizedOnNonDateColumn(): void
    {
        $field = new Date();
        $field->setColumnType('bigint(20)');

        $payload = 'myDate` = 1 OR SLEEP(5) -- ';
        $timestamp = strtotime('2026-09-11');
        $condition = $field->getFilterConditionExt($timestamp, '=', ['name' => $payload]);

        // the injected backtick must be doubled by the identifier quoter, not left raw
        $this->assertStringContainsString('``', $condition);
        $this->assertSame(
            '`myDate`` = 1 OR SLEEP(5) -- ` BETWEEN ',
            substr($condition, 0, strpos($condition, "'"))
        );
    }
}
