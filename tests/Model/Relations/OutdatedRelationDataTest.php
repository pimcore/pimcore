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

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Db;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\MultipleAssignments;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Relation changes are saved as a delta against the relation rows read for the object. These tests make sure the
 * delta is calculated against the current database state, not against rows read before a concurrent save or copied
 * from another object (https://github.com/pimcore/pimcore/issues/17604).
 *
 * @group model.relations.outdated
 */
class OutdatedRelationDataTest extends ModelTestCase
{
    private const FIELD = 'onlyOneManyToManyObject';

    /** @var RelationTest[] */
    private array $relationObjects = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        for ($i = 0; $i < 3; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath('__test/relationobjects'));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->save();
            $this->relationObjects[] = $object;
        }
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupPimcoreClass_MultipleAssignments();
    }

    public function testCopyContentsReplacesRelationsOfTarget(): void
    {
        [$x, $y, $z] = $this->relationObjects;
        $source = $this->createObject('source', [$x, $y]);
        $target = $this->createObject('target', [$x, $z]);

        (new Service())->copyContents(
            MultipleAssignments::getById($target->getId(), ['force' => true]),
            MultipleAssignments::getById($source->getId(), ['force' => true])
        );

        $this->assertSame($this->ids([$x, $y]), $this->storedRelationIds($target->getId()));

        // the target can be saved again
        $reloaded = MultipleAssignments::getById($target->getId(), ['force' => true]);
        $reloaded->setKey('target-renamed');
        $reloaded->save();
        $this->assertSame($this->ids([$x, $y]), $this->storedRelationIds($target->getId()));
    }

    public function testSavingOutdatedInstanceDoesNotDuplicateRelations(): void
    {
        [$x, $y] = $this->relationObjects;
        $object = $this->createObject('outdated', [$x]);

        $outdated = MultipleAssignments::getById($object->getId(), ['force' => true]);
        $outdated->getOnlyOneManyToManyObject();

        $current = MultipleAssignments::getById($object->getId(), ['force' => true]);
        $current->setOnlyOneManyToManyObject($this->metadata([$x, $y]));
        $current->save();

        $outdated->setOnlyOneManyToManyObject($this->metadata([$x, $y]));
        $outdated->save();

        $this->assertSame($this->ids([$x, $y]), $this->storedRelationIds($object->getId()));
    }

    public function testSavingOutdatedInstanceKeepsConcurrentChangesOfUntouchedField(): void
    {
        [$x, $y, $z] = $this->relationObjects;
        $object = $this->createObject('untouched', [$x]);

        $outdated = MultipleAssignments::getById($object->getId(), ['force' => true]);

        $current = MultipleAssignments::getById($object->getId(), ['force' => true]);
        $current->setOnlyOneManyToManyObject($this->metadata([$x, $y]));
        $current->save();

        // changes another field only, the outdated version count makes this a full save of all fields
        $outdated->setMultipleManyToManyObject($this->metadata([$z]));
        $outdated->save();

        $this->assertSame($this->ids([$x, $y]), $this->storedRelationIds($object->getId()));
        // the query table is written from the field value, it has to match the relation rows
        $this->assertSame(
            ',' . implode(',', $this->ids([$x, $y])) . ',',
            $this->storedQueryValue($object->getId())
        );
    }

    /**
     * @param RelationTest[] $relations
     */
    private function createObject(string $key, array $relations): MultipleAssignments
    {
        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey($key);
        $object->setPublished(true);
        $object->setOnlyOneManyToManyObject($this->metadata($relations));
        $object->save();

        return $object;
    }

    /**
     * @param RelationTest[] $relations
     *
     * @return ObjectMetadata[]
     */
    private function metadata(array $relations): array
    {
        return array_map(
            static fn (RelationTest $relation) => new ObjectMetadata(self::FIELD, ['meta'], $relation),
            $relations
        );
    }

    /**
     * @param RelationTest[] $relations
     *
     * @return int[]
     */
    private function ids(array $relations): array
    {
        $ids = array_map(static fn (RelationTest $relation) => $relation->getId(), $relations);
        sort($ids);

        return $ids;
    }

    /**
     * @return int[]
     */
    private function storedRelationIds(int $objectId): array
    {
        $ids = Db::get()->fetchFirstColumn(
            'SELECT dest_id FROM object_relations_' . MultipleAssignments::classId()
            . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'object' ORDER BY dest_id",
            [$objectId, self::FIELD]
        );

        return array_map('intval', $ids);
    }

    private function storedQueryValue(int $objectId): ?string
    {
        $value = Db::get()->fetchOne(
            'SELECT ' . self::FIELD . ' FROM object_query_' . MultipleAssignments::classId() . ' WHERE oo_id = ?',
            [$objectId]
        );

        return $value === false ? null : $value;
    }
}
