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

use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The grid filter field name must always be passed through the identifier-quoting helper
 * (which doubles embedded backticks) instead of being wrapped in raw backticks.
 */
class ManyToOneRelationFilterConditionTest extends TestCase
{
    // these tests only quote values via the DB connection and don't mutate data, so cleanup can stay disabled
    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return true;
    }

    public function testLegitimateFieldNameWithTypedValue(): void
    {
        $field = new ManyToOneRelation();

        $condition = $field->getFilterConditionExt('object|5', '=', ['name' => 'myRelation']);

        $this->assertSame("(`myRelation__type` = 'object' AND `myRelation__id` = '5')", $condition);
    }

    public function testLegitimateFieldNameWithBrickPrefix(): void
    {
        $field = new ManyToOneRelation();

        $condition = $field->getFilterConditionExt('asset|7', '=', ['name' => 'myRelation', 'brickPrefix' => '`myBrick`.']);

        $this->assertSame(
            "(`myBrick`.`myRelation__type` = 'asset' AND `myBrick`.`myRelation__id` = '7')",
            $condition
        );
    }

    public function testEmbeddedBacktickInFieldNameIsQuotedWithTypedValue(): void
    {
        $field = new ManyToOneRelation();

        $condition = $field->getFilterConditionExt('object|5', '=', ['name' => 'my`Relation']);

        $this->assertSame("(`my``Relation__type` = 'object' AND `my``Relation__id` = '5')", $condition);
    }

    public function testEmbeddedBacktickInFieldNameIsQuotedWithoutType(): void
    {
        $field = new ManyToOneRelation();

        $condition = $field->getFilterConditionExt('5', '=', ['name' => 'my`Relation']);

        $this->assertSame("`my``Relation__id` = '5'", $condition);
    }
}
