<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\MultipleAssignments;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class DeltaCalculationTest
 *
 * @package Pimcore\Tests\Model\Relations
 *
 * @group model.relations.multipleassignment
 */
class DeltaCalculationTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createRelationObjects();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function createRelationObjects(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath('__test/relationobjects'));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->setSomeAttribute("Some content $i");
            $object->save();
        }
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupPimcoreClass_MultipleAssignments();
    }

    public function testDeltaManyToMany(): void
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ElementMetadata('multipleManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $fds = $object->getClass()->getFieldDefinitions();
        $fd = $fds['multipleManyToMany'];

        // Insert 5 relations
        $object->setMultipleManyToMany($metaDataList);
        $this->deltaCheck([5, 0, 0, 0], $fd, $object);
        $object->save();

        // Remove last relation
        array_pop($metaDataList);
        $object->setMultipleManyToMany($metaDataList);
        $this->deltaCheck([0, 4, 0, 1], $fd, $object);
        $object->save();

        // Remove all
        $object->setMultipleManyToMany([]);
        $this->deltaCheck([0, 0, 0, 4], $fd, $object);
        $object->save();

        // Re-insert 4, also re-check existing are 0
        $object->setMultipleManyToMany($metaDataList);
        $this->deltaCheck([4, 0, 0, 0], $fd, $object);
        $object->save();

        // Swap 1 and 2
        $metaDataList = $this->swapOrder($metaDataList, 1, 2);
        $object->setMultipleManyToMany($metaDataList);
        $this->deltaCheck([0, 2, 2, 0], $fd, $object);
        $object->save();
        $multipleManyToMany = $object->getMultipleManyToMany();
        $this->metaOrderCheck([0, 2, 1, 3], $multipleManyToMany);

        // Swap 0 and 2 and delete first one at the same time
        $newMetaDataList = $object->getMultipleManyToMany();
        $newMetaDataList = $this->swapOrder($newMetaDataList, 0, 2);
        array_shift($newMetaDataList);
        $object->setMultipleManyToMany($newMetaDataList);
        $this->deltaCheck([0, 0, 3, 1], $fd, $object);
        $object->save();
        $multipleManyToMany = $object->getMultipleManyToMany();
        $this->metaOrderCheck([2, 0, 3], $multipleManyToMany);
    }

    /**
     * @param array $expectedValues Pass in the CRUD order, C for new, R for existing, U for updated, D for removed
     */
    protected function deltaCheck(array $expectedValues, $fd, $object): void
    {
        $delta = $fd->calculateDelta($object, [
            'context'=> [
                'containerType'=>'object',
            ],
        ]);

        $this->assertCount($expectedValues[0], $delta['newRelations'], 'New relations count');
        $this->assertCount($expectedValues[1], $delta['existingRelations'], 'Existing relations count');
        $this->assertCount($expectedValues[2], $delta['updatedRelations'], 'Updated relations count');
        $this->assertCount($expectedValues[3], $delta['removedRelations'], 'Removed relations count');
    }

    protected function metaOrderCheck(array $expectedValues, array $data): void
    {
        foreach ($data as $i => $relation) {
            $this->assertEquals('multiple-some-metadata '. $expectedValues[$i], $relation->getMeta(), 'Metadata order check');
        }

    }

    private function swapOrder(array $data, int $from, int $to): array
    {
        $temp = $data[$from];
        $data[$from] = $data[$to];
        $data[$to] = $temp;

        return $data;
    }
}
