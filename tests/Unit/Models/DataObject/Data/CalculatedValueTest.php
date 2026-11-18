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

namespace Pimcore\Tests\Unit\Model\DataObject\Data;

use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for https://github.com/pimcore/platform-version/issues/332:
 * Concrete::getValueForFieldName() creates a CalculatedValue without calling
 * setContextualData(), so all context getters must be safe to call on a fresh instance.
 */
class CalculatedValueTest extends TestCase
{
    public function testContextGettersAreSafeWithoutContextualData(): void
    {
        $value = new CalculatedValue('field');

        $this->assertSame('field', $value->getFieldname());
        $this->assertNull($value->getIndex());
        $this->assertSame('object', $value->getOwnerType());
        $this->assertNull($value->getOwnerName());
        $this->assertNull($value->getPosition());
        $this->assertNull($value->getGroupId());
        $this->assertNull($value->getKeyId());
        $this->assertNull($value->getKeyDefinition());
        $this->assertNull($value->getCalculatorData());
    }
}
