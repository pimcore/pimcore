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
use Pimcore\Model\DataObject\Csstore;

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

    protected function tearDown(): void
    {
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

        $backlog = $service->getBacklog($class);
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
     * This is also where the fourth of the comparison's exclusions is exercised: a collection
     * mapping the child shares with its parent is left out of the child's own rows deliberately,
     * and must not count as a divergence.
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

            $parent = new Csstore();
            $parent->setParentId(1);
            $parent->setKey('inherit-parent');
            $parent->setPublished(true);
            $parent->getCsstore()->setLocalizedKeyValue($groupId, $parentKeyId, 'from the parent');
            $parent->save();

            $child = new Csstore();
            $child->setParentId((int) $parent->getId());
            $child->setKey('inherit-child');
            $child->setPublished(true);
            $child->getCsstore()->setLocalizedKeyValue($groupId, $childKeyId, 'from the child');
            $child->save();

            $parentId = (int) $parent->getId();
            $childId = (int) $child->getId();

            $this->assertSame(
                'from the parent',
                $this->reload($childId)->getCsstore()->getLocalizedKeyValue($groupId, $parentKeyId),
                'precondition: the child inherits the parent value'
            );

            $this->flipStore();
            $service = $this->getMigrationService();

            // neither model was mutated: both are converted all the same
            foreach ([$parentId, $childId] as $objectId) {
                $this->assertSame(
                    DrainOutcome::Converted,
                    $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT)->outcome
                );
            }

            $childDocument = $this->readDocumentRows($childId);
            $this->assertCount(1, $childDocument);
            $this->assertStringContainsString('from the child', (string) $childDocument[0]['values']);
            $this->assertStringNotContainsString(
                'from the parent',
                (string) $childDocument[0]['values'],
                "the parent's value must not be written onto the child's document"
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
        } finally {
            $class = $this->getClass();
            $class->setAllowInherit(false);
            $class->save();
        }
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
