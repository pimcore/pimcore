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

use Exception;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\MultipleAssignments;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class MultipleAssigmentTest
 *
 * @package Pimcore\Tests\Model\Relations
 *
 * @group model.relations.multipleassignment
 */
class MultipleAssigmentTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createRelationObjects();
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

    public function testMultipleAssignmentsOnSingleManyToMany(): void
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ElementMetadata('onlyOneManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("single-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ElementMetadata('onlyOneManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("single-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setOnlyOneManyToMany($metaDataList);

        try {
            $object->save();
            $this->fail('only one assignment allowed but validation accepted duplicate items');
        } catch (Exception $e) {
        }
    }

    protected function checkMultipleAssignmentsOnSingleManyToMany(array $metaDataList, string $positionMessage = ''): void
    {
        $this->assertEquals(5, count($metaDataList), "Relation count $positionMessage.");
        foreach ($metaDataList as $i => $metadata) {
            $this->assertEquals("single-some-metadata $i", $metadata->getMeta(), "Metadata $positionMessage.");
        }
    }

    public function testMultipleAssignmentsOnSingleManyToManyObject(): void
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ObjectMetadata('onlyOneManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("single-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ObjectMetadata('onlyOneManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("single-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setOnlyOneManyToManyObject($metaDataList);

        try {
            $object->save();
            $this->fail('only one assignment allowed but validation accepted duplicate items');
        } catch (Exception $e) {
        }
    }

    protected function checkMultipleAssignmentsOnMultipleManyToMany(array $metaDataList, string $positionMessage = ''): void
    {
        $this->assertEquals(10, count($metaDataList), "Relation count $positionMessage.");
        $number = 0;
        foreach ($metaDataList as $i => $metadata) {
            if ($i % 2) {
                $this->assertEquals("multiple-some-more-metadata $number", $metadata->getMeta(), "Metadata $positionMessage.");
                $number++;
            } else {
                $this->assertEquals("multiple-some-metadata $number", $metadata->getMeta(), "Metadata $positionMessage.");
            }
        }
    }

    public function testMultipleAssignmentsMultipleManyToMany(): void
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

            $objectMetadata = new ElementMetadata('multipleManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setMultipleManyToMany($metaDataList);

        $object->save();

        $metaDataList = $object->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, ['force' => true]);

        $metaDataList = $object->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after serialize/unserialize');
    }

    public function testMultipleAssignmentsMultipleManyToManyObject(): void
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setMultipleManyToManyObject($metaDataList);

        $object->save();

        $metaDataList = $object->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, ['force' => true]);

        $metaDataList = $object->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after serialize/unserialize');
    }
}
