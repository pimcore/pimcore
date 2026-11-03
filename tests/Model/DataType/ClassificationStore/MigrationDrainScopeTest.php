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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Service as DataObjectService;

/**
 * What the drain reaches and what it leaves alone: every container that can define a classification
 * store field, no container filter at all, the stores that are still on EAV untouched - and a read
 * with inheritance off, so that a parent's values never land on a child's document.
 *
 * @group model.datatype.classificationstore
 */
class MigrationDrainScopeTest extends AbstractMigrationTest
{
    use MigrationClassFixtureTrait;

    private const SCOPE_CLASS_ID = 'CsMigrationScope';

    private const MIGRATED_AT = 1762000000;

    private const ROOT_FIELD = 'csScopeRoot';

    private const LOCALIZED_FIELD = 'csScopeLocalized';

    private const BRICK_FIELD = 'csScopeBrick';

    private const COLLECTION_FIELD = 'csScopeCollection';

    private const EAV_FIELD = 'csScopeEav';

    private ?CollectionConfig $collection = null;

    protected function tearDown(): void
    {
        $this->collection?->delete();
        $this->collection = null;

        $this->cleanUpMigrationFixture();

        parent::tearDown();
    }

    /**
     * Every container a classification store field can be defined in contributes its field name to
     * the scope, and a field whose store is still on EAV contributes none. The containers are not
     * told apart anywhere: both engines key their rows by (object, fieldname).
     */
    public function testEveryContainerContributesItsFieldNameAndAnEavStoreContributesNone(): void
    {
        $documentStoreId = $this->createMigrationStore();
        $eavStoreId = $this->createMigrationStore();
        $this->flipMigrationStore($documentStoreId);

        $class = $this->buildMigrationClass(self::SCOPE_CLASS_ID, [
            $this->migrationStoreField(self::ROOT_FIELD, $documentStoreId),
            $this->wrapInLocalizedFields($this->migrationStoreField(self::LOCALIZED_FIELD, $documentStoreId)),
            $this->registerMigrationBrick($this->migrationStoreField(self::BRICK_FIELD, $documentStoreId)),
            $this->registerMigrationFieldCollection(
                $this->migrationStoreField(self::COLLECTION_FIELD, $documentStoreId)
            ),
            $this->migrationStoreField(self::EAV_FIELD, $eavStoreId),
        ]);

        $fieldNames = $this->getMigrationService()->getDocumentFieldNames($class);
        sort($fieldNames);

        $this->assertSame(
            [self::BRICK_FIELD, self::COLLECTION_FIELD, self::LOCALIZED_FIELD, self::ROOT_FIELD],
            $fieldNames
        );
    }

    /**
     * And the conversion follows the scope: the rows of the object-root field and of the field
     * inside the object brick are drained, the rows of the field on the store nobody flipped are
     * left exactly where they are.
     */
    public function testTheDrainConvertsEveryContainerAndLeavesAStoreOnEavAlone(): void
    {
        $documentStoreId = $this->createMigrationStore();
        $eavStoreId = $this->createMigrationStore();

        $documentGroupId = $this->createMigrationGroup($documentStoreId);
        $documentKeyId = $this->createMigrationKey($documentStoreId, $documentGroupId);
        $eavGroupId = $this->createMigrationGroup($eavStoreId);
        $eavKeyId = $this->createMigrationKey($eavStoreId, $eavGroupId);

        $class = $this->saveMigrationClass(self::SCOPE_CLASS_ID, [
            $this->migrationStoreField(self::ROOT_FIELD, $documentStoreId),
            $this->registerMigrationBrick($this->migrationStoreField(self::BRICK_FIELD, $documentStoreId)),
            $this->migrationStoreField(self::EAV_FIELD, $eavStoreId),
        ]);

        // the value tables key their rows by (object, fieldname), so an object of any class is a
        // row these tables accept - the container the field was defined in is nowhere in them
        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'scope-owner')->getId();

        foreach ([self::ROOT_FIELD, self::BRICK_FIELD] as $fieldname) {
            $this->seedMigrationEavRows(
                self::SCOPE_CLASS_ID,
                $objectId,
                $fieldname,
                $documentGroupId,
                $documentKeyId,
                'converted: ' . $fieldname
            );
        }

        $this->seedMigrationEavRows(
            self::SCOPE_CLASS_ID,
            $objectId,
            self::EAV_FIELD,
            $eavGroupId,
            $eavKeyId,
            'left on eav'
        );

        $this->flipMigrationStore($documentStoreId);
        $service = $this->getMigrationService();

        $backlog = $this->pendingPairs($class);
        $this->assertSame(
            [[$objectId, self::BRICK_FIELD], [$objectId, self::ROOT_FIELD]],
            $this->sortPairs($backlog)
        );

        foreach ($backlog as [$pairObjectId, $fieldname]) {
            $this->assertSame(
                DrainOutcome::Converted,
                $service->drainPair($class, $pairObjectId, $fieldname, self::MIGRATED_AT)->outcome,
                $fieldname . ' has to convert'
            );
        }

        $this->assertSame(
            [self::BRICK_FIELD, self::ROOT_FIELD],
            $this->fieldNamesIn(self::DOCUMENT_TABLE_PREFIX . self::SCOPE_CLASS_ID)
        );
        $this->assertSame(
            [self::EAV_FIELD],
            $this->fieldNamesIn(self::DATA_TABLE_PREFIX . self::SCOPE_CLASS_ID)
        );
        $this->assertSame(
            [self::EAV_FIELD],
            $this->fieldNamesIn(self::GROUPS_TABLE_PREFIX . self::SCOPE_CLASS_ID)
        );
    }

    /**
     * The drain reads with inheritance off, so the child's document holds its own values alone and
     * the parent keeps answering the rest. It also converts a pair whose model was never mutated -
     * the dirty gate that makes a load-then-save write nothing does not apply to it.
     *
     * This is also where the fourth of the comparison's exclusions is exercised: both objects map
     * the group to the same collection on EAV, and the mapping the parent answers is left out of
     * the child's own row deliberately - which must not count as a divergence and must still read
     * back as the collection the child was saved with.
     */
    public function testTheDrainReadsWithInheritanceOffAndConvertsAnUntouchedPair(): void
    {
        $class = $this->getClass();
        $class->setAllowInherit(true);
        $class->save();

        try {
            $groupId = $this->getGroupId();
            $parentKeyId = $this->getKeyId(self::INPUT_KEY);
            $childKeyId = $this->getKeyId(self::TEXTAREA_KEY);
            $collectionId = $this->createCollectionOfGroup($groupId);

            $parent = new Csstore();
            $parent->setParentId(1);
            $parent->setKey('inherit-parent');
            $parent->setPublished(true);
            $parent->getCsstore()->setLocalizedKeyValue($groupId, $parentKeyId, 'from the parent');
            $parent->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
            $parent->save();

            $child = new Csstore();
            $child->setParentId((int) $parent->getId());
            $child->setKey('inherit-child');
            $child->setPublished(true);
            $child->getCsstore()->setLocalizedKeyValue($groupId, $childKeyId, 'from the child');
            $child->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
            $child->save();

            $parentId = (int) $parent->getId();
            $childId = (int) $child->getId();

            $this->assertSame(
                'from the parent',
                $this->reload($childId)->getCsstore()->getLocalizedKeyValue($groupId, $parentKeyId),
                'precondition: the child inherits the parent value'
            );
            $this->assertSame(
                [$collectionId, $collectionId],
                [$this->eavCollectionId($parentId, $groupId), $this->eavCollectionId($childId, $groupId)],
                'precondition: the EAV rows of both objects carry the mapping'
            );

            $this->flipStore();
            $service = $this->getMigrationService();

            // neither model was mutated: both are converted all the same
            foreach ([$parentId, $childId] as $objectId) {
                $result = $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

                $this->assertSame(DrainOutcome::Converted, $result->outcome, (string) $result->divergence);
            }

            $childDocument = $this->readDocumentRows($childId);
            $this->assertCount(1, $childDocument);
            $this->assertStringContainsString('from the child', (string) $childDocument[0]['values']);
            $this->assertStringNotContainsString(
                'from the parent',
                (string) $childDocument[0]['values'],
                "the parent's value must not be written onto the child's document"
            );

            $this->assertNull(
                $childDocument[0]['collectionId'],
                "the mapping the parent answers is left out of the child's own row"
            );
            $parentDocument = $this->readDocumentRows($parentId);
            $this->assertCount(1, $parentDocument);
            $this->assertSame(
                $collectionId,
                (int) $parentDocument[0]['collectionId'],
                'the parent, which has no inheritable parent of its own, stores it'
            );

            $this->assertSame(
                'from the parent',
                $this->reload($childId)->getCsstore()->getLocalizedKeyValue($groupId, $parentKeyId),
                'the parent value is still inherited at read time'
            );
            $this->assertSame(
                'from the child',
                $this->reload($childId)->getCsstore()->getLocalizedKeyValue($groupId, $childKeyId)
            );
            $this->assertSame(
                $collectionId,
                $this->inheritedCollectionIdOf($childId, $groupId),
                'and the collection is still resolved, through the parent that holds it'
            );
        } finally {
            $class = $this->getClass();
            $class->setAllowInherit(false);
            $class->save();
        }
    }

    /**
     * What the drain leaves behind in the caches that hold the objects it converted.
     *
     * Every pair is converted over a forced load of its object, and such a load registers the
     * object in the runtime cache - with the classification store it carries, which is not lazy
     * loaded. The rows that store was built from are the ones the conversion then moves, so the
     * entry is a view of the side the pair has left.
     *
     * Where that is wrong rather than merely old is a null collection mapping, the one thing the two
     * sides do not read the same way: the parent below maps the group to no collection, which on EAV
     * is an entry of its own and shadows the grandparent's mapping, while the document row it is
     * converted into carries null precisely so that the grandparent answers it. The child's own
     * mapping is therefore left out of its document, as an ancestor answers it - and a child reload
     * that resolves it through a parent still served from the cache would get that parent's null
     * instead of the collection the child was stored with. Only the child is reloaded here; its
     * ancestors are whatever the caches hold for them.
     */
    public function testAConvertedObjectIsNotServedFromTheCacheTheDrainLoadedItInto(): void
    {
        $class = $this->getClass();
        $class->setAllowInherit(true);
        $class->save();

        try {
            $groupId = $this->getGroupId();
            $keyId = $this->getKeyId(self::INPUT_KEY);
            $collectionId = $this->createCollectionOfGroup($groupId);

            $grandparent = new Csstore();
            $grandparent->setParentId(1);
            $grandparent->setKey('cache-grandparent');
            $grandparent->setPublished(true);
            $grandparent->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'from the grandparent');
            $grandparent->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
            $grandparent->save();

            // the parent holds a value of the group and maps it to no collection at all
            $parent = new Csstore();
            $parent->setParentId((int) $grandparent->getId());
            $parent->setKey('cache-parent');
            $parent->setPublished(true);
            $parent->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'from the parent');
            $parent->save();

            $child = new Csstore();
            $child->setParentId((int) $parent->getId());
            $child->setKey('cache-child');
            $child->setPublished(true);
            $child->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'from the child');
            $child->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
            $child->save();

            $objectIds = [
                (int) $grandparent->getId(),
                (int) $parent->getId(),
                (int) $child->getId(),
            ];
            $childId = $objectIds[2];

            $this->assertSame(
                $collectionId,
                $this->inheritedCollectionIdOf($childId, $groupId),
                'precondition: the child resolves the collection it was saved with'
            );

            $this->flipStore();
            $service = $this->getMigrationService();

            foreach ($objectIds as $objectId) {
                $result = $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

                $this->assertSame(DrainOutcome::Converted, $result->outcome, (string) $result->divergence);
            }

            $this->assertSame(
                $collectionId,
                $this->inheritedCollectionIdOf($childId, $groupId),
                'the child has to resolve it through ancestors read as the conversion left them'
            );
        } finally {
            $class = $this->getClass();
            $class->setAllowInherit(false);
            $class->save();
        }
    }

    /**
     * A classification store defined only inside an object brick, on a class that allows
     * inheritance, for a child object whose parent is inheritable too.
     *
     * Its rows are keyed by (object, fieldname) like any other's, but there is no
     * `getCsScopeBrick()` on the object - that getter belongs to the brick. Resolving what the
     * parent answers for the pair through such a getter therefore throws a BadMethodCallException
     * instead of converting the field, and the collection mappings of the child are resolved on both
     * sides of the conversion: when its own rows are written, and when the written document is
     * compared against them.
     */
    public function testADrainConvertsAContainerOnlyFieldOnAnInheritableChild(): void
    {
        $storeId = $this->createMigrationStore();
        $groupId = $this->createMigrationGroup($storeId);
        $keyId = $this->createMigrationKey($storeId, $groupId);

        $class = $this->saveMigrationClass(self::SCOPE_CLASS_ID, [
            $this->registerMigrationBrick($this->migrationStoreField(self::BRICK_FIELD, $storeId)),
        ]);
        $class->setAllowInherit(true);
        $class->save();

        // the objects are of the shipped class, whose value tables are not the ones seeded below:
        // what matters is that the child has an inheritable parent and that neither of them has a
        // getter for the field name the brick defines
        $owner = $this->getClass();
        $owner->setAllowInherit(true);
        $owner->save();

        try {
            $parent = new Csstore();
            $parent->setParentId(1);
            $parent->setKey('container-only-parent');
            $parent->setPublished(true);
            $parent->save();

            $child = new Csstore();
            $child->setParentId((int) $parent->getId());
            $child->setKey('container-only-child');
            $child->setPublished(true);
            $child->save();

            $childId = (int) $child->getId();
            $this->assertInstanceOf(
                Csstore::class,
                Csstore::getById($childId, ['force' => true]),
                'precondition: the child is a real inheritable object'
            );
            $this->assertFalse(
                method_exists(Csstore::class, 'get' . ucfirst(self::BRICK_FIELD)),
                'precondition: the field the brick defines has no getter on the object'
            );

            $this->seedMigrationEavRows(
                self::SCOPE_CLASS_ID,
                $childId,
                self::BRICK_FIELD,
                $groupId,
                $keyId,
                'defined inside a brick'
            );

            $this->flipMigrationStore($storeId);
            $service = $this->getMigrationService();

            $this->assertSame([[$childId, self::BRICK_FIELD]], $this->pendingPairs($class));

            $result = $service->drainPair($class, $childId, self::BRICK_FIELD, self::MIGRATED_AT);

            $this->assertSame(DrainOutcome::Converted, $result->outcome, (string) $result->divergence);
            $this->assertSame(
                [self::BRICK_FIELD],
                $this->fieldNamesIn(self::DOCUMENT_TABLE_PREFIX . self::SCOPE_CLASS_ID)
            );
            $this->assertSame([], $this->pendingPairs($class));
        } finally {
            $owner = $this->getClass();
            $owner->setAllowInherit(false);
            $owner->save();
        }
    }

    /**
     * A collection of the shared test store that the given group belongs to, so that an object can
     * map the group to it.
     */
    private function createCollectionOfGroup(int $groupId): int
    {
        $this->collection = new CollectionConfig();
        $this->collection->setStoreId($this->getStoreId());
        $this->collection->setName('cs-scope-collection-' . uniqid());
        $this->collection->save();

        $relation = new CollectionGroupRelation();
        $relation->setColId((int) $this->collection->getId());
        $relation->setGroupId($groupId);
        $relation->save();

        return (int) $this->collection->getId();
    }

    /**
     * The collection id the EAV rows of one object hold for one group, which is the side a mapping
     * is written on before the flip.
     */
    private function eavCollectionId(int $objectId, int $groupId): ?int
    {
        $collectionId = Db::get()->fetchOne(
            'SELECT `collectionId` FROM ' . Db::get()->quoteIdentifier($this->getDataTable())
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ? LIMIT 1',
            [$objectId, self::FIELDNAME, $groupId]
        );

        return $collectionId === false || $collectionId === null ? null : (int) $collectionId;
    }

    /**
     * What one object resolves a group's collection to with inheritance on, which is how a reader
     * asks for it: the object's own mappings merged with its ancestors'.
     */
    private function inheritedCollectionIdOf(int $objectId, int $groupId): ?int
    {
        $object = $this->reload($objectId);

        return DataObjectService::useInheritedValues(
            true,
            static fn (): ?int => $object->getCsstore()->getGroupCollectionMapping($groupId)
        );
    }

    /**
     * @param array<array{0: int, 1: string}> $pairs
     *
     * @return array<array{0: int, 1: string}>
     */
    private function sortPairs(array $pairs): array
    {
        usort($pairs, static fn (array $first, array $second): int => $first[1] <=> $second[1]);

        return $pairs;
    }

    /**
     * @return string[] the field names the table holds, sorted
     */
    private function fieldNamesIn(string $table): array
    {
        $fieldNames = array_map(
            strval(...),
            Db::get()->fetchFirstColumn('SELECT DISTINCT fieldname FROM ' . Db::get()->quoteIdentifier($table))
        );
        sort($fieldNames);

        return $fieldNames;
    }
}
