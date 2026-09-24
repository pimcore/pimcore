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

use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-8c9h-28x3-9f9f: classificationstore QuantityValue grid
 * filters must not allow raw SQL to be injected into the HAVING clause via
 * $value[0][0].
 */
class QuantityValueFilterConditionTest extends TestCase
{
    // these tests only quote values via the DB connection and don't mutate data, so cleanup can stay disabled
    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return true;
    }

    public function testLegitimateNumericValueIsFiltered(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            [['12.5', '1']],
            '=',
            ['name' => 'cskey_1-2']
        );

        $this->assertStringContainsString('12.5', $condition);
        $this->assertStringNotContainsString(',1', $condition, 'unit value must not leak into the value comparison');
    }

    public function testSqlInjectionPayloadIsRejected(): void
    {
        $field = new QuantityValue();

        $payload = '0 OR (SELECT 1 FROM users) -- ';

        $condition = $field->getFilterConditionExt(
            [[$payload, '0']],
            '=',
            ['name' => 'cskey_1-2']
        );

        $this->assertSame('1 = 0', $condition);
        $this->assertStringNotContainsString('SELECT', $condition);
    }

    public function testNonAllowlistedOperatorIsRejected(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            [['5', '0']],
            '1=1; DROP TABLE users; --',
            ['name' => 'cskey_1-2']
        );

        $this->assertSame('1 = 0', $condition);
    }

    public function testNonAllowlistedOperatorOnNonClassificationStorePathIsUnchangedForBackwardCompatibility(): void
    {
        // BC: unlike the cskey_ (classificationstore) branch above, this path is not part of
        // the reported vulnerability and is deliberately left unvalidated here to avoid a
        // behavior change for this public method's non-classificationstore callers. The actual
        // grid entry point (GridHelperService) already only ever forwards a small hardcoded
        // operator set ('=', 'LIKE', '<', '>', 'in') to this method, so this remains safe.
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            '5',
            'BETWEEN',
            ['name' => 'myQuantityValue__value']
        );

        $this->assertSame("`myQuantityValue__value` BETWEEN '5' ", $condition);
    }

    public function testHighPrecisionDecimalValueIsPreservedExactly(): void
    {
        // Confirms FilterValueFormatter is actually wired into this branch; the formatter's
        // own boundary cases (typical values, DECIMAL(65,30) precision, exponent overflow) are
        // covered directly and exhaustively in FilterValueFormatterTest.
        $field = new QuantityValue();

        $highPrecisionValue = '123456789012345678901234567890.123456789012345678901234567890';

        $condition = $field->getFilterConditionExt(
            [[$highPrecisionValue, '1']],
            '=',
            ['name' => 'cskey_1-2']
        );

        $this->assertSame("`cskey_1-2`.`value` = '" . $highPrecisionValue . "' ", $condition);
    }

    public function testInOperatorProducesValidListOnNonClassificationStorePath(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            '1.5,2.3,10',
            'in',
            ['name' => 'myQuantityValue__value']
        );

        $this->assertSame("`myQuantityValue__value` IN ('1.5','2.3','10') ", $condition);
    }

    public function testInOperatorProducesValidListOnClassificationStorePath(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            [['1.5,2.3,10', '1']],
            'in',
            ['name' => 'cskey_1-2']
        );

        $this->assertSame("`cskey_1-2`.`value` IN ('1.5','2.3','10') ", $condition);
    }

    public function testInOperatorRejectsSqlInjectionPayload(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            '1,2) OR (1=1',
            'in',
            ['name' => 'myQuantityValue__value']
        );

        $this->assertSame('1 = 0', $condition);
    }

    public function testInOperatorRejectsEmptyList(): void
    {
        $field = new QuantityValue();

        $condition = $field->getFilterConditionExt(
            '',
            'in',
            ['name' => 'myQuantityValue__value']
        );

        $this->assertSame('1 = 0', $condition);
    }
}
