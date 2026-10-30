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
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\MultipleAssignments;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Advanced relations must not store a row for a metadata column without a value.
 *
 * @group model.relations.nullmetadata
 */
class NullMetadataStorageTest extends ModelTestCase
{
    /**
     * @var RelationTest[]
     */
    private array $targets = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        foreach ([0, 1] as $i) {
            $target = new RelationTest();
            $target->setParent(Service::createFolderByPath('__test/relationobjects'));
            $target->setKey("relation-$i");
            $target->setPublished(true);
            $target->save();
            $this->targets[] = $target;
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

    private function createOwner(): MultipleAssignments
    {
        $owner = new MultipleAssignments();
        $owner->setParent(Service::createFolderByPath('/assignments'));
        $owner->setKey('null-metadata');
        $owner->setPublished(true);

        return $owner;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMetadataRows(MultipleAssignments $owner, string $fieldname): array
    {
        return Db::get()->fetchAllAssociative(
            'SELECT `dest_id`, `column`, `data` FROM object_metadata_' . $owner->getClassId()
            . ' WHERE `id` = ? AND `fieldname` = ? ORDER BY `index`',
            [$owner->getId(), $fieldname]
        );
    }

    public function testObjectMetadataWithoutValueStoresNoRow(): void
    {
        $withValue = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $withValue->setMeta('some value');
        $withoutValue = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[1]);

        $owner = $this->createOwner();
        $owner->setMultipleManyToManyObject([$withValue, $withoutValue]);
        $owner->save();

        $rows = $this->fetchMetadataRows($owner, 'multipleManyToManyObject');
        $this->assertCount(1, $rows, 'Only the column with a value is stored.');
        $this->assertSame($this->targets[0]->getId(), (int) $rows[0]['dest_id']);
        $this->assertSame('some value', $rows[0]['data']);

        $loaded = MultipleAssignments::getById($owner->getId(), ['force' => true]);
        $relations = $loaded->getMultipleManyToManyObject();
        $this->assertCount(2, $relations, 'The relation without metadata is still loaded.');
        $this->assertSame('some value', $relations[0]->getMeta());
        $this->assertNull($relations[1]->getMeta());
        $this->assertSame($this->targets[1]->getId(), $relations[1]->getElement()->getId());
    }

    public function testElementMetadataWithoutValueStoresNoRow(): void
    {
        $withValue = new ElementMetadata('multipleManyToMany', ['meta'], $this->targets[0]);
        $withValue->setMeta('some value');
        $withoutValue = new ElementMetadata('multipleManyToMany', ['meta'], $this->targets[1]);

        $owner = $this->createOwner();
        $owner->setMultipleManyToMany([$withValue, $withoutValue]);
        $owner->save();

        $rows = $this->fetchMetadataRows($owner, 'multipleManyToMany');
        $this->assertCount(1, $rows, 'Only the column with a value is stored.');
        $this->assertSame($this->targets[0]->getId(), (int) $rows[0]['dest_id']);

        $loaded = MultipleAssignments::getById($owner->getId(), ['force' => true]);
        $relations = $loaded->getMultipleManyToMany();
        $this->assertCount(2, $relations);
        $this->assertSame('some value', $relations[0]->getMeta());
        $this->assertNull($relations[1]->getMeta());
    }

    public function testClearingMetadataValueRemovesStoredRow(): void
    {
        $metadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $metadata->setMeta('some value');

        $owner = $this->createOwner();
        $owner->setMultipleManyToManyObject([$metadata]);
        $owner->save();
        $this->assertCount(1, $this->fetchMetadataRows($owner, 'multipleManyToManyObject'));

        $cleared = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $owner->setMultipleManyToManyObject([$cleared]);
        $owner->save();

        $this->assertCount(0, $this->fetchMetadataRows($owner, 'multipleManyToManyObject'));
        $loaded = MultipleAssignments::getById($owner->getId(), ['force' => true]);
        $this->assertNull($loaded->getMultipleManyToManyObject()[0]->getMeta());
    }

    public function testReloadedRelationKeepsNullColumnsAndIsEqualToEditmodeData(): void
    {
        $withValue = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $withValue->setMeta('some value');
        $withoutValue = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[1]);

        $owner = $this->createOwner();
        $owner->setMultipleManyToManyObject([$withValue, $withoutValue]);
        $owner->save();

        $loaded = MultipleAssignments::getById($owner->getId(), ['force' => true]);
        $relations = $loaded->getMultipleManyToManyObject();
        $this->assertSame(['meta' => null], $relations[1]->getData(), 'The null column stays in the data.');

        // the editmode/import path sets every column explicitly, also with null
        $fromEditmode = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[1]);
        $fromEditmode->setMeta(null);
        $unchanged = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $unchanged->setMeta('some value');

        $fieldDefinition = $loaded->getClass()->getFieldDefinition('multipleManyToManyObject');
        $this->assertTrue($fieldDefinition->isEqual($relations, [$unchanged, $fromEditmode]));
    }

    public function testReloadedElementRelationKeepsNullColumns(): void
    {
        $withoutValue = new ElementMetadata('multipleManyToMany', ['meta'], $this->targets[1]);

        $owner = $this->createOwner();
        $owner->setMultipleManyToMany([$withoutValue]);
        $owner->save();

        $loaded = MultipleAssignments::getById($owner->getId(), ['force' => true]);
        $this->assertSame(['meta' => null], $loaded->getMultipleManyToMany()[0]->getData());
    }

    public function testDirectSaveWithNullRemovesPreviouslyStoredValue(): void
    {
        $metadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $this->targets[0]);
        $metadata->setMeta('some value');

        $owner = $this->createOwner();
        $owner->setMultipleManyToManyObject([$metadata]);
        $owner->save();
        $this->assertCount(1, $this->fetchMetadataRows($owner, 'multipleManyToManyObject'));

        $metadata->setMeta(null);
        $metadata->save($owner, 'object', '', '0', 1);

        $this->assertCount(0, $this->fetchMetadataRows($owner, 'multipleManyToManyObject'));
    }

    public function testReloadingSameInstanceResetsClearedColumns(): void
    {
        $metadata = new ObjectMetadata('multipleManyToManyObject', ['meta', 'other'], $this->targets[0]);
        $metadata->setMeta('some value');
        $metadata->setOther('other value');

        $owner = $this->createOwner();
        $owner->setMultipleManyToManyObject([$metadata]);
        $owner->save();

        Db::get()->executeStatement(
            'DELETE FROM object_metadata_' . $owner->getClassId() . ' WHERE `id` = ? AND `column` = ?',
            [$owner->getId(), 'meta']
        );

        $metadata->load($owner, $this->targets[0]->getId(), 'multipleManyToManyObject', 'object', '', '0', 1);
        $this->assertNull($metadata->getMeta());
        $this->assertSame('other value', $metadata->getOther());
    }
}
