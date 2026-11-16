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

use Exception;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\LazyLoadingLocalizedTest as FieldcollectionLocalizedItem;
use Pimcore\Model\DataObject\Fieldcollection\Data\LazyLoadingTest as FieldcollectionItem;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest as LocalizedBrick;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest as Brick;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Model\HookedLazyLoading;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;

/**
 * Like a lazy loaded relation of the object itself, one in localized fields, a field collection or an object brick
 * that was not read in this request cannot have been changed by it. Saving the object must neither load every
 * related element to validate it again nor to resolve the dependencies of the object, and the outcome must be the
 * same as with every relation loaded.
 *
 * @group model.dataobject.lazyrelation
 */
class LazyNestedRelationSaveTest extends ModelTestCase
{
    private const LOCALIZED_FIELDS = 'localized fields';

    private const FIELD_COLLECTION = 'field collection';

    private const FIELD_COLLECTION_LOCALIZED_FIELDS = 'localized fields of a field collection';

    private const OBJECT_BRICK = 'object brick';

    private const OBJECT_BRICK_LOCALIZED_FIELDS = 'localized fields of an object brick';

    private bool $inheritedValues = false;

    private bool $hideUnpublished = false;

    protected function setUp(): void
    {
        $this->inheritedValues = DataObject::getGetInheritedValues();
        $this->hideUnpublished = DataObject::getHideUnpublished();

        parent::setUp();

        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        DataObject::setGetInheritedValues($this->inheritedValues);
        DataObject::setHideUnpublished($this->hideUnpublished);

        TestHelper::cleanUp();

        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_LazyLoadingTest();
        $this->tester->setupFieldcollection_LazyLoadingLocalizedTest();
        $this->tester->setupPimcoreClass_LazyLoading();
        $this->tester->setupObjectbrick_LazyLoadingTest();
        $this->tester->setupObjectbrick_LazyLoadingLocalizedTest();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideContainers(): array
    {
        return [
            self::LOCALIZED_FIELDS => [self::LOCALIZED_FIELDS],
            self::FIELD_COLLECTION => [self::FIELD_COLLECTION],
            self::FIELD_COLLECTION_LOCALIZED_FIELDS => [self::FIELD_COLLECTION_LOCALIZED_FIELDS],
            self::OBJECT_BRICK => [self::OBJECT_BRICK],
            self::OBJECT_BRICK_LOCALIZED_FIELDS => [self::OBJECT_BRICK_LOCALIZED_FIELDS],
        ];
    }

    /**
     * Saving a field collection deletes the relation rows of its localized fields and writes them again from the
     * loaded values, so those still have to be loaded.
     *
     * @return array<string, array{string}>
     */
    public static function provideContainersKeepingTheirRelationRows(): array
    {
        $containers = self::provideContainers();
        unset($containers[self::FIELD_COLLECTION_LOCALIZED_FIELDS]);

        return $containers;
    }

    /**
     * @return array<string, array{callable(LazyLoading): void}>
     */
    public static function provideChanges(): array
    {
        return [
            'a field of the object' => [static function (LazyLoading $object): void {
                $object->setInput('changed');
            }],
            'a field of a field collection item' => [static function (LazyLoading $object): void {
                foreach ($object->getFieldcollection()->getItems() as $item) {
                    if ($item instanceof FieldcollectionLocalizedItem) {
                        $item->setNormalInput('changed');
                    }
                }
            }],
            'a new field collection item' => [static function (LazyLoading $object): void {
                $object->getFieldcollection()->add(new FieldcollectionLocalizedItem());
            }],
            'a removed field collection item, the others move up' => [static function (LazyLoading $object): void {
                $object->getFieldcollection()->remove(0);
            }],
            'a field of the object without dirty detection' => [static function (LazyLoading $object): void {
                $object->setInput('changed');
                DataObject::disableDirtyDetection();
            }],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideInheritingContainers(): array
    {
        // field collections do not inherit
        return [
            self::LOCALIZED_FIELDS => [self::LOCALIZED_FIELDS],
            self::OBJECT_BRICK => [self::OBJECT_BRICK],
            self::OBJECT_BRICK_LOCALIZED_FIELDS => [self::OBJECT_BRICK_LOCALIZED_FIELDS],
        ];
    }

    /**
     * The relations of the localized fields of a field collection are loaded, see
     * provideContainersKeepingTheirRelationRows().
     */
    public function testSaveDoesNotLoadUntouchedNestedRelations(): void
    {
        $targets = $this->createTargets(3);
        [$single] = $this->createTargets(1);
        $collectionTargets = $this->createTargets(2);
        $owner = $this->createOwnerWithAllNestedRelations($targets, $single, $collectionTargets);

        $owner = $this->reloadAndChange($owner);
        $this->assertSame(0, $this->saveAndCountValidationLoads($owner, [...$targets, $single]));

        $this->assertStoredNestedRelations($owner, $targets, $single, $collectionTargets);
    }

    /**
     * Whatever the save writes, the nested relations that were not loaded must be stored unchanged afterwards.
     *
     * @dataProvider provideChanges
     */
    public function testSaveKeepsUntouchedNestedRelations(callable $change): void
    {
        $targets = $this->createTargets(3);
        [$single] = $this->createTargets(1);
        $collectionTargets = $this->createTargets(2);
        $owner = $this->createOwnerWithAllNestedRelations($targets, $single, $collectionTargets);

        $unloaded = $this->reload($owner);
        $unloaded->setOmitMandatoryCheck(false);

        try {
            $change($unloaded);
            $unloaded->save();
        } finally {
            DataObject::enableDirtyDetection();
        }

        $this->assertStoredNestedRelations($owner, $targets, $single, $collectionTargets);
    }

    public function testSaveStillLoadsAnUntouchedRelationInTheLocalizedFieldsOfAFieldCollection(): void
    {
        $targets = $this->createTargets(2);
        $owner = $this->createOwner(self::FIELD_COLLECTION_LOCALIZED_FIELDS, $targets);

        $this->assertGreaterThan(0, $this->saveAndCountValidationLoads($this->reloadAndChange($owner), $targets));
    }

    /**
     * Like for a relation of the object itself, a many-to-one relation still loads its single element.
     */
    public function testResolveDependenciesDoesNotLoadUntouchedNestedRelations(): void
    {
        $targets = $this->createTargets(3);
        [$single] = $this->createTargets(1);
        $collectionTargets = $this->createTargets(2);
        $owner = $this->createOwnerWithAllNestedRelations($targets, $single, $collectionTargets);

        $unloaded = $this->reload($owner);
        $dependencies = $this->countLoads(
            [...$targets, ...$collectionTargets],
            static fn () => $unloaded->resolveDependencies(),
            $loads
        );

        $this->assertSame(0, $loads);
        $this->assertEquals($this->loadedDependencies($owner), $dependencies);
        foreach ([...$targets, $single, ...$collectionTargets] as $target) {
            $this->assertArrayHasKey('object_' . $target->getId(), $dependencies);
        }
    }

    /**
     * @dataProvider provideContainersKeepingTheirRelationRows
     */
    public function testSaveDoesNotLoadAnUntouchedMandatoryNestedRelationThatHasRows(string $container): void
    {
        $targets = $this->createTargets(2);
        $owner = $this->createOwner($container, $targets);

        $this->withRelationSettings($container, true, null, function () use ($owner, $targets): void {
            $this->assertSame(0, $this->saveAndCountValidationLoads($this->reloadAndChange($owner), $targets));
        });
    }

    /**
     * @dataProvider provideContainers
     */
    public function testSaveStillFailsForAnUntouchedEmptyMandatoryNestedRelation(string $container): void
    {
        $owner = $this->createOwner($container, []);

        $this->withRelationSettings($container, true, null, function () use ($owner): void {
            $message = $this->assertSameSaveOutcome($owner);
            $this->assertNotNull($message, 'an empty mandatory relation must fail the validation');
        });
    }

    /**
     * @dataProvider provideContainersKeepingTheirRelationRows
     */
    public function testSaveDoesNotLoadAnUntouchedNestedRelationWithinMaxItems(string $container): void
    {
        $targets = $this->createTargets(2);
        $owner = $this->createOwner($container, $targets);

        $this->withRelationSettings($container, false, 2, function () use ($owner, $targets): void {
            $this->assertSame(0, $this->saveAndCountValidationLoads($this->reloadAndChange($owner), $targets));
        });
    }

    /**
     * @dataProvider provideContainers
     */
    public function testSaveStillFailsForAnUntouchedNestedRelationExceedingMaxItems(string $container): void
    {
        $owner = $this->createOwner($container, $this->createTargets(2));

        $this->withRelationSettings($container, false, 1, function () use ($owner): void {
            $message = $this->assertSameSaveOutcome($owner);
            $this->assertStringContainsString('Number of allowed relations in field `', (string) $message);
        });
    }

    /**
     * The relation row of a deleted element stays, but the element is left out like in the loaded relation.
     *
     * @dataProvider provideContainers
     */
    public function testUntouchedNestedRelationToDeletedElementsMatchesTheLoadedRelation(string $container): void
    {
        $targets = $this->createTargets(2);
        $owner = $this->createOwner($container, $targets);
        $targets[0]->delete();
        $targets[1]->delete();

        $this->withRelationSettings($container, true, null, function () use ($owner): void {
            $this->assertNotNull($this->assertSameSaveOutcome($owner), 'a relation to deleted elements is empty');
        });
        $this->withRelationSettings($container, false, 1, function () use ($owner): void {
            $this->assertNull($this->assertSameSaveOutcome($owner), 'deleted elements do not count');
        });
        $this->assertDependenciesMatchLoadedRelations($owner);
    }

    /**
     * While unpublished elements are hidden, the getter of a field collection or a brick leaves them out, the
     * localized fields validate and resolve their stored value with them. Both must stay as they are.
     *
     * @dataProvider provideContainers
     */
    public function testUntouchedNestedRelationToUnpublishedElementsMatchesTheLoadedRelation(string $container): void
    {
        $owner = $this->createOwner($container, $this->createTargets(2, false));

        foreach ([true, false] as $hideUnpublished) {
            DataObject::setHideUnpublished($hideUnpublished);

            $this->withRelationSettings($container, true, null, function () use ($owner): void {
                $this->assertSameSaveOutcome($owner);
            });
            $this->withRelationSettings($container, false, 1, function () use ($owner): void {
                $this->assertSameSaveOutcome($owner);
            });
            $this->assertDependenciesMatchLoadedRelations($owner);
        }
    }

    /**
     * Without own relation rows the value of a nested relation may be inherited from the parent.
     *
     * @dataProvider provideInheritingContainers
     */
    public function testUntouchedNestedRelationOfAChildMatchesTheLoadedRelation(string $container): void
    {
        $parent = $this->createOwner($container, $this->createTargets(2));
        $child = $this->createOwner($container, [], $parent);

        foreach ([true, false] as $inheritedValues) {
            DataObject::setGetInheritedValues($inheritedValues);

            $this->withRelationSettings($container, true, null, function () use ($child): void {
                $this->assertSameSaveOutcome($child);
            });
            $this->withRelationSettings($container, false, 1, function () use ($child): void {
                $this->assertSameSaveOutcome($child);
            });
            $this->assertDependenciesMatchLoadedRelations($child);
        }
    }

    /**
     * A pre get value hook may return the container of another object. Its relations are judged by the rows stored
     * for that object, the ones they would be loaded from.
     *
     * @dataProvider provideContainers
     */
    public function testUntouchedNestedRelationInTheValueOfAPreGetValueHookMatchesTheLoadedRelation(
        string $container
    ): void {
        $owner = $this->createOwner($container, []);
        $other = $this->createOwner($container, $this->createTargets(2));
        $this->assertFalse(Pimcore::inAdmin(), 'precondition: the hook is only called outside of the admin');

        $hooked = function (bool $loadAll) use ($container, $owner, $other): HookedLazyLoading {
            $getter = 'get' . ucfirst($this->containerField($container));
            HookedLazyLoading::setHookedValue($this->containerField($container), $this->reload($other)->$getter());

            $object = HookedLazyLoading::fromStored($owner);
            $object->setOmitMandatoryCheck(false);
            if ($loadAll) {
                Service::loadAllObjectFields($object);
            }

            return $object;
        };

        try {
            $this->withRelationSettings($container, false, 1, function () use ($hooked): void {
                $outcome = $this->saveOutcome($hooked(false));
                $this->assertSame($this->saveOutcome($hooked(true)), $outcome);
                $this->assertStringContainsString('Number of allowed relations in field `', (string) $outcome);
            });

            $this->assertEquals($hooked(true)->resolveDependencies(), $hooked(false)->resolveDependencies());
        } finally {
            HookedLazyLoading::resetHookedValues();
        }
    }

    /**
     * A localized relation is set in every language, all of them are required in the validation.
     *
     * @param list<RelationTest> $targets
     */
    private function createOwner(string $container, array $targets, ?LazyLoading $parent = null): LazyLoading
    {
        $owner = new LazyLoading();
        $owner->setParentId($parent?->getId() ?? 1);
        $owner->setKey('lazy-nested-' . uniqid());
        $owner->setPublished(true);
        $owner->setOmitMandatoryCheck(true);

        switch ($container) {
            case self::LOCALIZED_FIELDS:
                foreach (Tool::getValidLanguages() as $language) {
                    $owner->setLobjects($targets, $language);
                }

                break;
            case self::FIELD_COLLECTION:
                $item = new FieldcollectionItem();
                $item->setObjects($targets);
                $owner->setFieldcollection(new Fieldcollection([$item]));

                break;
            case self::FIELD_COLLECTION_LOCALIZED_FIELDS:
                $item = new FieldcollectionLocalizedItem();
                foreach (Tool::getValidLanguages() as $language) {
                    $item->setLobjects($targets, $language);
                }
                $owner->setFieldcollection(new Fieldcollection([$item]));

                break;
            case self::OBJECT_BRICK:
                $brick = new Brick($owner);
                $brick->setObjects($targets);
                $owner->getBricks()->setLazyLoadingTest($brick);

                break;
            case self::OBJECT_BRICK_LOCALIZED_FIELDS:
                $brick = new LocalizedBrick($owner);
                foreach (Tool::getValidLanguages() as $language) {
                    $brick->setLobjects($targets, $language);
                }
                $owner->getBricks()->setLazyLoadingLocalizedTest($brick);

                break;
        }

        $owner->save();

        return $owner;
    }

    /**
     * @param list<RelationTest> $targets the elements of the many-to-many relations
     * @param RelationTest $single the element of the many-to-one relations
     * @param list<RelationTest> $collectionTargets the elements of the localized fields of a field collection
     */
    private function createOwnerWithAllNestedRelations(
        array $targets,
        RelationTest $single,
        array $collectionTargets
    ): LazyLoading {
        $owner = new LazyLoading();
        $owner->setParentId(1);
        $owner->setKey('lazy-nested-all-' . uniqid());
        $owner->setPublished(true);
        $owner->setOmitMandatoryCheck(true);

        foreach (['en', 'de'] as $language) {
            $owner->setLobjects($targets, $language);
            $owner->setLrelations($targets, $language);
            $owner->setLrelation($single, $language);
            $owner->setLadvancedObjects($this->objectMetadata('ladvancedObjects', 'metadata', $targets), $language);
            $owner->setLadvancedRelations(
                $this->elementMetadata('ladvancedRelations', 'metadata', $targets),
                $language
            );
        }

        // an item without relations in front, removing it moves the others
        $item = new FieldcollectionItem();
        $this->setAllRelations($item, $targets, $single);
        $localizedItem = new FieldcollectionLocalizedItem();
        $localizedItem->setLobjects($collectionTargets, 'en');
        $localizedItem->setLrelations($collectionTargets, 'de');
        $localizedItem->setLadvancedObjects(
            $this->objectMetadata('ladvancedObjects', 'metadata', $collectionTargets),
            'en'
        );
        $owner->setFieldcollection(new Fieldcollection([new FieldcollectionItem(), $item, $localizedItem]));

        $brick = new Brick($owner);
        $this->setAllRelations($brick, $targets, $single);
        $owner->getBricks()->setLazyLoadingTest($brick);
        $localizedBrick = new LocalizedBrick($owner);
        $localizedBrick->setLobjects($targets, 'en');
        $localizedBrick->setLrelations($targets, 'de');
        $owner->getBricks()->setLazyLoadingLocalizedTest($localizedBrick);

        $owner->save();

        return $owner;
    }

    /**
     * @param list<RelationTest> $targets
     * @param list<RelationTest> $collectionTargets
     */
    private function assertStoredNestedRelations(
        LazyLoading $owner,
        array $targets,
        RelationTest $single,
        array $collectionTargets
    ): void {
        $stored = $this->reload($owner);
        $ids = $this->ids($targets);

        foreach (['en', 'de'] as $language) {
            $this->assertSame($ids, $this->ids($stored->getLobjects($language)), 'lobjects ' . $language);
            $this->assertSame($ids, $this->ids($stored->getLrelations($language)), 'lrelations ' . $language);
            $this->assertSame($single->getId(), $stored->getLrelation($language)?->getId(), 'lrelation ' . $language);
            $this->assertMetadata($targets, $stored->getLadvancedObjects($language));
            $this->assertMetadata($targets, $stored->getLadvancedRelations($language));
        }

        // the item with the relations is the last plain one, the first localized one is the one with relations
        $items = $stored->getFieldcollection()->getItems();
        $plainItems = array_values(array_filter($items, static fn ($item) => $item instanceof FieldcollectionItem));
        $item = end($plainItems);
        $this->assertInstanceOf(FieldcollectionItem::class, $item);
        $this->assertAllRelations($item, $targets, $single);

        $localizedItems = array_values(array_filter(
            $items,
            static fn ($item) => $item instanceof FieldcollectionLocalizedItem
        ));
        $localizedItem = $localizedItems[0] ?? null;
        $this->assertInstanceOf(FieldcollectionLocalizedItem::class, $localizedItem);
        $this->assertSame($this->ids($collectionTargets), $this->ids($localizedItem->getLobjects('en')));
        $this->assertSame($this->ids($collectionTargets), $this->ids($localizedItem->getLrelations('de')));
        $this->assertMetadata($collectionTargets, $localizedItem->getLadvancedObjects('en'));

        $brick = $stored->getBricks()->getLazyLoadingTest();
        $this->assertInstanceOf(Brick::class, $brick);
        $this->assertAllRelations($brick, $targets, $single);

        $localizedBrick = $stored->getBricks()->getLazyLoadingLocalizedTest();
        $this->assertInstanceOf(LocalizedBrick::class, $localizedBrick);
        $this->assertSame($ids, $this->ids($localizedBrick->getLobjects('en')));
        $this->assertSame($ids, $this->ids($localizedBrick->getLrelations('de')));
    }

    /**
     * @param list<RelationTest> $targets
     */
    private function assertAllRelations(
        FieldcollectionItem|Brick $container,
        array $targets,
        RelationTest $single
    ): void {
        $ids = $this->ids($targets);
        $this->assertSame($ids, $this->ids($container->getObjects()), 'objects');
        $this->assertSame($ids, $this->ids($container->getRelations()), 'relations');
        $this->assertSame($single->getId(), $container->getRelation()?->getId(), 'relation');
        $this->assertMetadata($targets, $container->getAdvancedObjects());
        $this->assertMetadata($targets, $container->getAdvancedRelations());
    }

    /**
     * Asserts the related elements and their metadata, see metadataValue().
     *
     * @param list<RelationTest> $targets
     * @param array<ObjectMetadata|ElementMetadata> $metadata
     */
    private function assertMetadata(array $targets, array $metadata): void
    {
        $expected = [];
        foreach ($targets as $target) {
            $expected[] = [$target->getId(), $this->metadataValue($target)];
        }

        $actual = [];
        foreach ($metadata as $item) {
            $data = array_values($item->getData());
            $actual[] = [$item->getElement()?->getId(), $data[0] ?? null];
        }

        $this->assertSame($expected, $actual);
    }

    private function metadataValue(RelationTest $target): string
    {
        return 'meta-' . $target->getId();
    }

    /**
     * @param list<RelationTest> $targets
     */
    private function setAllRelations(FieldcollectionItem|Brick $container, array $targets, RelationTest $single): void
    {
        $container->setObjects($targets);
        $container->setRelations($targets);
        $container->setRelation($single);
        $container->setAdvancedObjects($this->objectMetadata('advancedObjects', 'metadataUpper', $targets));
        $container->setAdvancedRelations($this->elementMetadata('advancedRelations', 'metadataUpper', $targets));
    }

    /**
     * @param list<RelationTest> $targets
     *
     * @return list<ObjectMetadata>
     */
    private function objectMetadata(string $fieldname, string $column, array $targets): array
    {
        return array_map(function (RelationTest $target) use ($fieldname, $column): ObjectMetadata {
            $metadata = new ObjectMetadata($fieldname, [$column], $target);
            $metadata->setData([$column => $this->metadataValue($target)]);

            return $metadata;
        }, $targets);
    }

    /**
     * @param list<RelationTest> $targets
     *
     * @return list<ElementMetadata>
     */
    private function elementMetadata(string $fieldname, string $column, array $targets): array
    {
        return array_map(function (RelationTest $target) use ($fieldname, $column): ElementMetadata {
            $metadata = new ElementMetadata($fieldname, [$column], $target);
            $metadata->setData([$column => $this->metadataValue($target)]);

            return $metadata;
        }, $targets);
    }

    /**
     * @return list<RelationTest>
     */
    private function createTargets(int $count, bool $published = true): array
    {
        $targets = [];
        for ($i = 0; $i < $count; $i++) {
            $target = new RelationTest();
            $target->setParent(Service::createFolderByPath('__test/lazy-nested-targets'));
            $target->setKey('target-' . uniqid('', true));
            $target->setPublished($published);
            $target->save();
            $targets[] = $target;
        }

        return $targets;
    }

    /**
     * The field of the object that holds the container.
     */
    private function containerField(string $container): string
    {
        return match ($container) {
            self::LOCALIZED_FIELDS => 'localizedfields',
            self::FIELD_COLLECTION, self::FIELD_COLLECTION_LOCALIZED_FIELDS => 'fieldcollection',
            self::OBJECT_BRICK, self::OBJECT_BRICK_LOCALIZED_FIELDS => 'bricks',
        };
    }

    private function nestedRelation(string $container): ManyToManyObjectRelation
    {
        $fd = match ($container) {
            self::LOCALIZED_FIELDS => $this->localizedRelation(ClassDefinition::getByName('LazyLoading')),
            self::FIELD_COLLECTION => FieldcollectionDefinition::getByKey('LazyLoadingTest')
                ->getFieldDefinition('objects'),
            self::FIELD_COLLECTION_LOCALIZED_FIELDS => $this->localizedRelation(
                FieldcollectionDefinition::getByKey('LazyLoadingLocalizedTest')
            ),
            self::OBJECT_BRICK => ObjectbrickDefinition::getByKey('LazyLoadingTest')->getFieldDefinition('objects'),
            self::OBJECT_BRICK_LOCALIZED_FIELDS => $this->localizedRelation(
                ObjectbrickDefinition::getByKey('LazyLoadingLocalizedTest')
            ),
        };
        $this->assertInstanceOf(ManyToManyObjectRelation::class, $fd);

        return $fd;
    }

    private function localizedRelation(
        ClassDefinition|FieldcollectionDefinition|ObjectbrickDefinition $definition
    ): ?ClassDefinition\Data {
        $localizedfields = $definition->getFieldDefinition('localizedfields');
        $this->assertInstanceOf(Localizedfields::class, $localizedfields);

        return $localizedfields->getFieldDefinition('lobjects');
    }

    private function withRelationSettings(string $container, bool $mandatory, ?int $maxItems, callable $test): void
    {
        $fd = $this->nestedRelation($container);
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

    private function reload(Concrete $object): LazyLoading
    {
        $reloaded = LazyLoading::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(LazyLoading::class, $reloaded);

        return $reloaded;
    }

    private function reloadAndChange(Concrete $object): LazyLoading
    {
        $object = $this->reload($object);
        $object->setInput('changed');
        $object->setOmitMandatoryCheck(false);

        return $object;
    }

    /**
     * Saves the object once with its nested relations not loaded and once with all of them loaded, the way it was
     * validated before, and asserts that both end the same.
     *
     * @return string|null the message of the validation exception, null if the object was saved
     */
    private function assertSameSaveOutcome(LazyLoading $object): ?string
    {
        $unloaded = $this->reloadAndChange($object);
        $outcome = $this->saveOutcome($unloaded);

        $loaded = $this->reloadAndChange($object);
        Service::loadAllObjectFields($loaded);

        $this->assertSame($this->saveOutcome($loaded), $outcome);

        return $outcome;
    }

    private function saveOutcome(LazyLoading $object): ?string
    {
        try {
            $object->save();
        } catch (ValidationException $e) {
            return $e->getMessage();
        }

        return null;
    }

    private function assertDependenciesMatchLoadedRelations(LazyLoading $object): void
    {
        $this->assertEquals($this->loadedDependencies($object), $this->reload($object)->resolveDependencies());
    }

    /**
     * The dependencies as resolved from the loaded relations, the way they were resolved before.
     *
     * @return array<string, array{id: int, type: string}>
     */
    private function loadedDependencies(LazyLoading $object): array
    {
        $loaded = $this->reload($object);
        Service::loadAllObjectFields($loaded);

        return $loaded->resolveDependencies();
    }

    /**
     * Saves the object and counts the loads of the given targets between the pre-update event and the end of the
     * validation in Concrete::update().
     *
     * @param list<RelationTest> $targets
     */
    private function saveAndCountValidationLoads(Concrete $object, array $targets): int
    {
        $validating = false;
        $start = static function () use (&$validating): void {
            $validating = true;
        };
        $end = static function () use (&$validating): void {
            $validating = false;
        };

        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE, $start, -10000);
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $end, 10000);

        try {
            $this->countLoads($targets, static fn () => $object->save(), $loads, $validating);
        } finally {
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE, $start);
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $end);
        }

        return $loads;
    }

    /**
     * Runs the callback and counts the loads of the given targets, while $counting is true.
     *
     * @param list<RelationTest> $targets
     */
    private function countLoads(array $targets, callable $callback, ?int &$loads, bool &$counting = true): mixed
    {
        $targetIds = $this->ids($targets);
        $loads = 0;
        $count = static function (DataObjectEvent $event) use (&$counting, &$loads, $targetIds): void {
            if ($counting && in_array($event->getObject()->getId(), $targetIds, true)) {
                $loads++;
            }
        };

        // the targets are cached since they were created, a load from the cache does not dispatch the event; the
        // definitions stay cached, their relation settings may have been changed for the test
        $targetKeys = array_map(static fn (int $id) => 'object_' . $id, $targetIds);
        RuntimeCache::clear(array_values(array_diff(
            array_keys(RuntimeCache::getInstance()->getArrayCopy()),
            $targetKeys
        )));

        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $count);

        try {
            return $callback();
        } catch (Exception $e) {
            $this->fail('Saving the object failed: ' . $e->getMessage());
        } finally {
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $count);
        }
    }

    /**
     * @param array<Concrete> $objects
     *
     * @return list<int>
     */
    private function ids(array $objects): array
    {
        return array_map(static fn (Concrete $object) => (int) $object->getId(), array_values($objects));
    }
}
