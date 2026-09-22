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

use Pimcore\Model\DataObject\ClassDefinition\Data\Image;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @group unit.model.datatype.image
 */
class ImageTest extends TestCase
{
    /**
     * Regression test for #18939: the object grid's relation filter for an Image field
     * submits the asset id as an int. getFilterConditionExt() forwarded it straight to
     * getRelationFilterCondition(?string $value, ...), which threw a TypeError under
     * strict_types before the filter condition was ever built.
     */
    public function testGetFilterConditionExtAcceptsIntValue(): void
    {
        $field = new Image();
        $field->setName('image');

        $condition = $field->getFilterConditionExt(123, '=', ['name' => 'image']);

        $this->assertStringContainsString('123', $condition);
    }

    public function testGetFilterConditionExtAcceptsNullValue(): void
    {
        $field = new Image();
        $field->setName('image');

        $condition = $field->getFilterConditionExt(null, '=', ['name' => 'image']);

        $this->assertStringContainsString('IS NULL', $condition);
    }
}
