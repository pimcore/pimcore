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

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * A lazy loaded relation that was not read in this request cannot have been changed by it - a setter marks the
 * field as loaded - and it is not written either. Saving the object must not load every related element only
 * to validate it again. Only the checks that do not need the related elements (mandatory, max. items) are left,
 * and the stored relation rows answer them unless they are not conclusive.
 *
 * The tests count the loads inside the validation only: writing a version and resolving the dependencies read
 * every field on their own.
 *
 * @group model.dataobject.lazyrelation
 */
class LazyRelationSaveValidationTest extends ModelTestCase
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

    public function testSaveDoesNotLoadAnUntouchedLazyRelation(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(2);

        $owner = $this->reloadAndChange($owner);

        $this->assertSame(0, $this->saveAndCountValidationLoads($owner, $targets));

        // the relation is not written, so it is still stored unchanged
        $owner = Unittest::getById($owner->getId(), ['force' => true]);
        $this->assertSame('changed', $owner->getInput());
        $this->assertSame($this->ids($targets), $this->ids($owner->getObjects()));
    }

    public function testSaveDoesNotLoadAnUntouchedMandatoryLazyRelationThatHasRows(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(2);

        $this->withRelationSettings(true, null, function () use ($owner, $targets): void {
            $owner = $this->reloadAndChange($owner);
            $owner->setOmitMandatoryCheck(false);

            $this->assertSame(0, $this->saveAndCountValidationLoads($owner, $targets));
        });
    }

    public function testSaveStillFailsForAnUntouchedEmptyMandatoryLazyRelation(): void
    {
        $owner = TestHelper::createEmptyObject('lazy-relation-empty-owner-');

        $this->withRelationSettings(true, null, function () use ($owner): void {
            $owner = $this->reloadAndChange($owner);
            $owner->setOmitMandatoryCheck(false);

            try {
                $owner->save();
                $this->fail('A mandatory relation without any related element must fail the validation');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Empty mandatory field [ objects ]', $e->getMessage());
            }
        });
    }

    /**
     * The relation row of a deleted element stays, but the getter leaves the element out, so the field is empty.
     */
    public function testSaveStillFailsForAnUntouchedMandatoryLazyRelationWithOnlyDeletedElements(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(1);
        $targets[0]->delete();

        $this->withRelationSettings(true, null, function () use ($owner): void {
            $owner = $this->reloadAndChange($owner);
            $owner->setOmitMandatoryCheck(false);

            try {
                $owner->save();
                $this->fail('A mandatory relation to deleted elements only must fail the validation');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Empty mandatory field [ objects ]', $e->getMessage());
            }
        });
    }

    /**
     * The relation row of a deleted element does not count against the max. items, like with the getter.
     */
    public function testSaveIgnoresDeletedElementsForTheMaxItemsOfAnUntouchedLazyRelation(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(2);
        $targets[1]->delete();

        $this->withRelationSettings(false, 1, function () use ($owner, $targets): void {
            $owner = $this->reloadAndChange($owner);

            $this->assertSame(0, $this->saveAndCountValidationLoads($owner, [$targets[0]]));
        });
    }

    public function testSaveDoesNotLoadAnUntouchedLazyRelationWithinMaxItems(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(2);

        $this->withRelationSettings(false, 2, function () use ($owner, $targets): void {
            $owner = $this->reloadAndChange($owner);

            $this->assertSame(0, $this->saveAndCountValidationLoads($owner, $targets));
        });
    }

    public function testSaveStillFailsForAnUntouchedLazyRelationExceedingMaxItems(): void
    {
        [$owner] = $this->createOwnerWithTargets(2);

        $this->withRelationSettings(false, 1, function () use ($owner): void {
            $owner = $this->reloadAndChange($owner);

            try {
                $owner->save();
                $this->fail('A stored relation exceeding the max. items must still fail the validation');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Number of allowed relations in field `objects` exceeded', $e->getMessage());
            }
        });
    }

    public function testSaveStillValidatesATouchedLazyRelation(): void
    {
        [$owner, $targets] = $this->createOwnerWithTargets(2);

        $this->withRelationSettings(false, 1, function () use ($owner, $targets): void {
            $owner = Unittest::getById($owner->getId(), ['force' => true]);
            $owner->setObjects($targets);

            try {
                $owner->save();
                $this->fail('A relation set in this request must be validated');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Number of allowed relations in field `objects` exceeded', $e->getMessage());
            }
        });
    }

    /**
     * Without own relation rows the value of a mandatory relation may be inherited from the parent, so the
     * validation falls back to the getter and its retry with inherited values.
     */
    public function testSaveAcceptsAnUntouchedMandatoryLazyRelationInheritedFromTheParent(): void
    {
        $target = TestHelper::createEmptyObject('lazy-relation-inherited-target-');

        $parent = $this->createInheritance('lazy-relation-parent-', 1);
        $parent->setRelationobjects([$target]);
        $parent->save();

        $child = $this->createInheritance('lazy-relation-child-', $parent->getId());
        $child->save();

        $fd = $child->getClass()->getFieldDefinition('relationobjects');
        $this->assertInstanceOf(ManyToManyObjectRelation::class, $fd);
        $mandatory = $fd->getMandatory();
        $fd->setMandatory(true);

        try {
            $child = Inheritance::getById($child->getId(), ['force' => true]);
            $child->setNormalinput('changed');
            $child->setOmitMandatoryCheck(false);
            $child->save();

            $child = Inheritance::getById($child->getId(), ['force' => true]);
            $this->assertSame('changed', $child->getNormalinput());
        } finally {
            $fd->setMandatory($mandatory);
        }
    }

    /**
     * Without own relation rows and with inherited values, the getter returns the relations of the parent, so their
     * number is checked against the max. items like before.
     */
    public function testSaveStillChecksTheMaxItemsOfAnUntouchedLazyRelationInheritedFromTheParent(): void
    {
        $targets = [
            TestHelper::createEmptyObject('lazy-relation-inherited-max-'),
            TestHelper::createEmptyObject('lazy-relation-inherited-max-'),
        ];

        $parent = $this->createInheritance('lazy-relation-max-parent-', 1);
        $parent->setRelationobjects($targets);
        $parent->save();

        $child = $this->createInheritance('lazy-relation-max-child-', $parent->getId());
        $child->save();

        $fd = $child->getClass()->getFieldDefinition('relationobjects');
        $this->assertInstanceOf(ManyToManyObjectRelation::class, $fd);
        $maxItems = $fd->getMaxItems();
        $inheritedValues = DataObject::getGetInheritedValues();
        $fd->setMaxItems(1);
        DataObject::setGetInheritedValues(true);

        try {
            $child = Inheritance::getById($child->getId(), ['force' => true]);
            $child->setNormalinput('changed');

            try {
                $child->save();
                $this->fail('The inherited relations exceed the max. items');
            } catch (ValidationException $e) {
                $this->assertStringContainsString(
                    'Number of allowed relations in field `relationobjects` exceeded',
                    $e->getMessage()
                );
            }
        } finally {
            $fd->setMaxItems($maxItems);
            DataObject::setGetInheritedValues($inheritedValues);
        }
    }

    /**
     * @return array{Unittest, list<Unittest>}
     */
    private function createOwnerWithTargets(int $count): array
    {
        $targets = [];
        for ($i = 0; $i < $count; $i++) {
            $targets[] = TestHelper::createEmptyObject('lazy-relation-target-');
        }

        $owner = TestHelper::createEmptyObject('lazy-relation-owner-', false);
        $owner->setObjects($targets);
        $owner->save();

        return [$owner, $targets];
    }

    private function createInheritance(string $keyPrefix, int $parentId): Inheritance
    {
        $object = new Inheritance();
        $object->setOmitMandatoryCheck(true);
        $object->setParentId($parentId);
        $object->setKey($keyPrefix . uniqid());
        $object->setPublished(true);

        return $object;
    }

    /**
     * Loads the object without its lazy fields and changes a field other than the relation.
     */
    private function reloadAndChange(Concrete $object): Unittest
    {
        $object = Unittest::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Unittest::class, $object);
        $this->assertFalse($object->isLazyKeyLoaded('objects'), 'precondition: the relation is not loaded');
        $object->setInput('changed');

        return $object;
    }

    private function withRelationSettings(bool $mandatory, ?int $maxItems, callable $test): void
    {
        $fd = DataObject\ClassDefinition::getByName('unittest')->getFieldDefinition('objects');
        $this->assertInstanceOf(ManyToManyObjectRelation::class, $fd);

        $originalMandatory = $fd->getMandatory();
        $originalMaxItems = $fd->getMaxItems();
        $fd->setMandatory($mandatory);
        $fd->setMaxItems($maxItems);

        try {
            $test();
        } finally {
            $fd->setMandatory($originalMandatory);
            $fd->setMaxItems($originalMaxItems);
        }
    }

    /**
     * Saves the object and counts the loads of the given targets between the pre-update event and the end of the
     * validation in Concrete::update().
     *
     * @param list<Concrete> $targets
     */
    private function saveAndCountValidationLoads(Concrete $object, array $targets): int
    {
        $targetIds = $this->ids($targets);
        $validating = false;
        $loads = 0;

        $start = static function () use (&$validating): void {
            $validating = true;
        };
        $end = static function () use (&$validating): void {
            $validating = false;
        };
        $count = static function (DataObjectEvent $event) use (&$validating, &$loads, $targetIds): void {
            if ($validating && in_array($event->getObject()->getId(), $targetIds, true)) {
                $loads++;
            }
        };

        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE, $start, -10000);
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $end, 10000);
        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $count);

        // the targets are cached since they were created, a load from the cache does not dispatch the event
        RuntimeCache::clear();

        try {
            $object->save();
        } finally {
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE, $start);
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $end);
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $count);
        }

        return $loads;
    }

    /**
     * @param array<Concrete> $objects
     *
     * @return list<int|null>
     */
    private function ids(array $objects): array
    {
        return array_map(static fn (Concrete $object) => $object->getId(), array_values($objects));
    }
}
