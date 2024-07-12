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

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ObjectbrickTest
 *
 * @package Pimcore\Tests\Model\Relations
 *
 * @group model.relations.objectbrick
 */
class ObjectbrickTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupObjectbrick_LazyLoadingTest();
    }

    public function testRelationFieldInsideObjectbrick(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        /**
         * @var $object DataObject\Unittest
         */
        $object = TestHelper::createEmptyObject();

        $brick = new DataObject\Objectbrick\Data\UnittestBrick($object);
        $brick->setBrickLazyRelation([$target1, $target2]);
        $object->getMybricks()->setUnittestBrick($brick);
        $object->save();

        $rel = $brick->getBrickLazyRelation();
        $this->assertCount(2, $rel);

        /**
         * @var $object DataObject\Unittest
         */
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $brick = $object->getMybricks()->getUnittestBrick();
        $brick->setBrickLazyRelation([$target2]);
        $rel = $brick->getBrickLazyRelation();
        $this->assertCount(1, $rel);
        $object->save();

        /**
         * @var $object DataObject\Unittest
         */
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $brick = $object->getMybricks()->getUnittestBrick();
        $rel = $brick->getBrickLazyRelation();
        $this->assertCount(1, $rel);
        $this->assertEquals($target2->getId(), $rel[0]->getId());

        //Flush relations
        $brick->setBrickLazyRelation(null);
        $object->save();

        /**
         * @var $object DataObject\Unittest
         */
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $brick = $object->getMybricks()->getUnittestBrick();
        $rel = $brick->getBrickLazyRelation();
        $this->assertEquals([], $rel);
    }
}
