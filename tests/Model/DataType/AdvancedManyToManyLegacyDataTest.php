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

use Pimcore\Model\DataObject;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Old versions hold plain relation entries after a many-to-many-object field was converted to an advanced one.
 *
 * @group model.datatype.advancedmanytomany
 */
class AdvancedManyToManyLegacyDataTest extends ModelTestCase
{
    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_Unittest();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    // a version saved with the old field type holds the related objects themselves, not metadata entries
    public function testObjectRelationNormalizeSkipsLegacyEntries(): void
    {
        $object = TestHelper::createEmptyObject();
        $metadata = new DataObject\Data\ObjectMetadata('objectswithmetadata', ['meta'], $object);
        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation();

        $normalized = $fd->normalize([$object, $metadata]);

        $this->assertCount(1, $normalized);
        $this->assertSame($object->getId(), $normalized[0]['element']['id']);
        $this->assertSame([], $fd->normalize([$object]));
    }

    public function testElementRelationNormalizeSkipsLegacyEntries(): void
    {
        $object = TestHelper::createEmptyObject();
        $metadata = new DataObject\Data\ElementMetadata('multihrefmetadata', ['meta'], $object);
        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyRelation();

        $normalized = $fd->normalize([$object, $metadata]);

        $this->assertCount(1, $normalized);
        $this->assertSame($object->getId(), $normalized[0]['element']['id']);
        $this->assertSame([], $fd->normalize([$object]));
    }
}
