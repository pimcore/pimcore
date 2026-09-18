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

        $this->assertSame('', $condition);
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

        $this->assertSame('', $condition);
    }
}
