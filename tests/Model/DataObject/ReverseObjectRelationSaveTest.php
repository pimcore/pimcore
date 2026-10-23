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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * A reverse relation is the read-only mirror of a relation that is stored on the owning
 * objects. Its getter loads every owning object, so saving an object must not read it
 * (unless the field is mandatory and has to be checked), otherwise the save of an object
 * with a large reverse relation exhausts the memory limit.
 *
 * @group model.dataobject.reverseobjectrelation
 */
class ReverseObjectRelationSaveTest extends ModelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * The unittest class relates to itself: `objects` is the owning side and `nonowner` is
     * its reverse relation, so one owner and one target of the same class are enough.
     */
    public function testSaveDoesNotLoadTheReverseRelation(): void
    {
        $target = TestHelper::createEmptyObject('reverse-relation-target-');
        $owner = TestHelper::createEmptyObject('reverse-relation-owner-', false);
        $owner->setObjects([$target]);
        $owner->save();

        $target = Unittest::getById($target->getId(), ['force' => true]);

        // precondition: reading the reverse relation loads the owning object into the runtime
        // cache, which is what this test uses to detect a read
        RuntimeCache::clear();
        $this->assertCount(1, $target->getNonowner());
        $this->assertTrue(RuntimeCache::isRegistered('object_' . $owner->getId()), 'precondition: the getter loads the owner');

        $target = Unittest::getById($target->getId(), ['force' => true]);
        $target->setInput('changed');

        RuntimeCache::clear();
        $target->save();

        $this->assertFalse(
            RuntimeCache::isRegistered('object_' . $owner->getId()),
            'saving the target must not load the objects of its reverse relation'
        );

        // the relation is still stored on the owning side and readable afterwards
        $target = Unittest::getById($target->getId(), ['force' => true]);
        $this->assertSame('changed', $target->getInput());
        $related = $target->getNonowner();
        $this->assertCount(1, $related);
        $this->assertSame($owner->getId(), $related[0]->getId());
    }

    /**
     * A mandatory reverse relation still needs its value for the mandatory check.
     */
    public function testSaveStillChecksAMandatoryReverseRelation(): void
    {
        $target = TestHelper::createEmptyObject('reverse-relation-mandatory-', false);
        $target->setOmitMandatoryCheck(false);
        $fd = $target->getClass()->getFieldDefinition('nonowner');
        $this->assertInstanceOf(ReverseObjectRelation::class, $fd);

        $mandatory = $fd->getMandatory();
        $fd->setMandatory(true);

        try {
            $target->save();
            $this->fail('A mandatory reverse relation without any owner must fail the validation');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('nonowner', $e->getMessage());
        } finally {
            $fd->setMandatory($mandatory);
        }
    }

    /**
     * Without the mandatory check (an unpublished object) the value of a mandatory reverse
     * relation is not needed either, so saving must not load its owners.
     */
    public function testSaveWithoutMandatoryCheckDoesNotLoadAMandatoryReverseRelation(): void
    {
        $target = TestHelper::createEmptyObject('reverse-relation-omit-target-');
        $owner = TestHelper::createEmptyObject('reverse-relation-omit-owner-', false);
        $owner->setObjects([$target]);
        $owner->save();

        $target = Unittest::getById($target->getId(), ['force' => true]);
        $target->setInput('changed');
        $target->setOmitMandatoryCheck(true);
        $fd = $target->getClass()->getFieldDefinition('nonowner');
        $this->assertInstanceOf(ReverseObjectRelation::class, $fd);

        $mandatory = $fd->getMandatory();
        $fd->setMandatory(true);

        try {
            RuntimeCache::clear();
            $target->save();

            $this->assertFalse(
                RuntimeCache::isRegistered('object_' . $owner->getId()),
                'saving without the mandatory check must not load the objects of a mandatory reverse relation'
            );
        } finally {
            $fd->setMandatory($mandatory);
        }
    }
}
