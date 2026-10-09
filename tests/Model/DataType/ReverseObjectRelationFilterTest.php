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

use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Covers the grid filter conditions ReverseObjectRelation builds.
 *
 * @group model.datatype.reverseobjectrelation
 *
 * @internal
 */
class ReverseObjectRelationFilterTest extends ModelTestCase
{
    private const NO_RESULT = '1 = 0';

    private function field(?string $ownerClassId = 'ABC'): ReverseObjectRelation
    {
        $fd = new ReverseObjectRelation();
        $fd->setName('reverseRelation');
        $fd->setOwnerFieldName('relation');
        if ($ownerClassId !== null) {
            $fd->setOwnerClassId($ownerClassId);
        }

        return $fd;
    }

    public function testEmptyValueSelectsTheUnreferencedObjects(): void
    {
        $condition = $this->field()->getFilterConditionExt(null, '=', ['tablePrefix' => 'o_.']);

        $this->assertStringContainsString('o_.id NOT IN (', $condition);
        $this->assertStringContainsString('SELECT dest_id FROM object_relations_ABC', $condition);
        $this->assertStringContainsString("ownertype = 'object'", $condition);
        $this->assertStringContainsString(
            "type = 'object'",
            $condition,
            'the destination type must be constrained, or an asset dest_id sharing an object id excludes that object'
        );
    }

    public function testTheStringNullIsTreatedAsAnEmptyValue(): void
    {
        $this->assertSame(
            $this->field()->getFilterConditionExt(null, '=', ['tablePrefix' => 'o_.']),
            $this->field()->getFilterConditionExt('null', '=', ['tablePrefix' => 'o_.'])
        );
    }

    public function testAValueSelectsTheReferencedObjects(): void
    {
        $condition = $this->field()->getFilterConditionExt(7, '=', ['tablePrefix' => 'o_.']);

        $this->assertStringContainsString('o_.id IN (', $condition);
        $this->assertStringContainsString('`src_id` = ', $condition);
    }

    /**
     * getOwnerClassId() returns null when the owner class cannot be resolved. There is no relation
     * table to query then, which is the case load() answers with an empty result.
     */
    public function testAMissingOwnerClassYieldsNoResult(): void
    {
        $fd = $this->field(null);

        $this->assertSame(self::NO_RESULT, $fd->getFilterConditionExt(null, '=', ['tablePrefix' => 'o_.']));
        $this->assertSame(self::NO_RESULT, $fd->getFilterConditionExt(7, '=', ['tablePrefix' => 'o_.']));
    }

    public function testAMissingTablePrefixIsRejected(): void
    {
        $this->expectExceptionMessage('called without a table prefix');

        $this->field()->getFilterConditionExt(null, '=', []);
    }
}
