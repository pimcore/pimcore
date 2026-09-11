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

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Covers the grid filter condition Select builds, in particular the empty value.
 *
 * @group model.datatype.select
 *
 * @internal
 */
class SelectFilterConditionTest extends ModelTestCase
{
    private function field(): Select
    {
        $fd = new Select();
        $fd->setName('status');

        return $fd;
    }

    /**
     * An unset select is stored as NULL by a fresh save and as '' by an edit that clears it, so
     * "no value" has to match both - otherwise the rows a user is looking for are split across a
     * filter that finds neither.
     */
    public function testAnEmptyValueMatchesNullAndTheEmptyString(): void
    {
        $condition = $this->field()->getFilterConditionExt('', '=', ['name' => 'status']);

        $this->assertStringContainsString('IS NULL', $condition);
        $this->assertStringContainsString("= ''", $condition);
        $this->assertStringContainsString('OR', $condition);
    }

    public function testNullIsTreatedAsAnEmptyValue(): void
    {
        $this->assertSame(
            $this->field()->getFilterConditionExt('', '=', ['name' => 'status']),
            $this->field()->getFilterConditionExt(null, '=', ['name' => 'status'])
        );
    }

    public function testANonEmptyValueIsMatchedExactly(): void
    {
        $condition = $this->field()->getFilterConditionExt('draft', '=', ['name' => 'status']);

        $this->assertStringContainsString("= 'draft'", $condition);
        $this->assertStringNotContainsString('IS NULL', $condition, 'a value filter must not also match unset rows');
    }

    /**
     * '0' is a legitimate select value, and a loose emptiness check would swallow it.
     */
    public function testZeroIsAValueRatherThanAnEmptyFilter(): void
    {
        $condition = $this->field()->getFilterConditionExt('0', '=', ['name' => 'status']);

        $this->assertStringContainsString("= '0'", $condition);
        $this->assertStringNotContainsString('IS NULL', $condition);
    }

    public function testTheBrickPrefixIsKeptOnTheEmptyCondition(): void
    {
        $condition = $this->field()->getFilterConditionExt('', '=', ['name' => 'status', 'brickPrefix' => 'brick.']);

        $this->assertStringContainsString('brick.`status` IS NULL', $condition);
    }
}
