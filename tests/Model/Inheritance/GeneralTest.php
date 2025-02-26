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

namespace Pimcore\Tests\Model\Inheritance;

use Doctrine\DBAL\Connection;
use Exception;
use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

class GeneralTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *        |-two
     *
     * two is created after one. two gets moved out and moved in again. Then one gets updated.
     */
    public function testInheritance(): void
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setNormalInput('parenttext');
        $one->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);
        $two->setNormalInput('childtext');
        $two->save();

        $id1 = $one->getId();
        $id2 = $two->getId();

        $one = DataObject::getById($id1);
        $two = DataObject::getById($id2);

        $this->assertEquals('parenttext', $one->getNormalInput());
        // not inherited
        $this->assertEquals('childtext', $two->getNormalInput());

        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = DataObject::getById($id2);
        $this->assertEquals('parenttext', $two->getNormalInput());

        $list = new Inheritance\Listing();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale('de');
        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items');

        // set it back
        $two->setNormalInput('childtext');
        $two->save();
        $two = DataObject::getById($id2);

        $list = new Inheritance\Listing();
        $list->setCondition("normalinput LIKE '%parenttext%'");
        $list->setLocale('de');
        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for de');

        // null it out
        $two->setNormalInput(null);
        $two->save();
        $two = DataObject::getById($id2);
        $this->assertEquals('parenttext', $two->getNormalInput());

        // disable inheritance
        DataObject\Service::useInheritedValues(false, function () use ($id2) {
            $two = DataObject::getById($id2);
            $this->assertEquals(null, $two->getNormalInput());
        });

        // enable inheritance
        DataObject\Service::useInheritedValues(true, function () use ($id2) {
            $two = DataObject::getById($id2);
            $this->assertEquals('parenttext', $two->getNormalInput());
        });

        // now move it out

        $two->setParentId(1);
        $two->save();

        // value must be null now
        $this->assertEquals(null, $two->getNormalInput());

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals('parenttext', $two->getNormalInput());

        // modify parent object
        $one->setNormalInput('parenttext2');
        $one->save();

        $two = DataObject::getById($id2);
        // check that child objects has been updated as well
        $this->assertEquals('parenttext2', $two->getNormalInput());

        // TODO the following doesn't work as the catch catches the exception thrown in fail
    }

    /**
     * Tests https://github.com/pimcore/pimcore/pull/6269
     * [Data objects] Override inherited value with same value (break inheritance)
     *
     * @throws Exception
     */
    public function testEqual(): void
    {
        // According to the bootstrap file en and de are valid website languages

        $target = new RelationTest();
        $target->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target->setKey('relation-1');
        $target->setPublished(true);
        $target->setSomeAttribute('Some content 1');
        $target->save();

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setNormalInput('parenttext');
        $one->setRelation($target);
        $one->save();

        // create child "two", inherit relation from "one"

        $two = new Inheritance();
        $two->setKey('one');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        $two->setNormalInput('parenttext');
        $two->save();
        $twoId = $two->getId();

        DataObject\Service::useInheritedValues(true, function () use ($two, $target) {
            $fetchedTarget = $two->getRelation();
            $this->assertTrue($fetchedTarget && $fetchedTarget->getId() == $target->getId(), 'expectected inherited target');
        });

        DataObject\Service::useInheritedValues(false, function () use ($two) {
            $fetchedTarget = $two->getRelation();
            $this->assertNull($fetchedTarget, 'target should not be inherited');
        });

        // enable inheritance and set the target
        DataObject\Service::useInheritedValues(true, function () use ($twoId, $target) {
            $two = Concrete::getById($twoId, ['force' => true]);
            $two->setRelation($target);
            $two->save();
        });

        // disable inheritance and check that the relation has been set on "two"
        DataObject\Service::useInheritedValues(false, function () use ($twoId, $target) {
            $two = Concrete::getById($twoId, ['force' => true]);
            $fetchedTarget = $two->getRelation();
            $this->assertTrue($fetchedTarget && $fetchedTarget->getId() == $target->getId(), 'expectected inherited target');
        });
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *      | -folder
     *         |-two
     *
     * object relations field should inherit it's values from one to two
     */
    public function testInheritanceWithFolder(): void
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setNormalInput('parenttext');
        $one->save();

        $folder = new Folder();
        $folder->setParent($one);
        $folder->setKey('folder');
        $folder->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($folder->getId());
        $two->setPublished(true);

        $two->setNormalInput('childtext');
        $two->save();

        $one->setRelationobjects([$one]);
        $one->save();

        Pimcore::collectGarbage();

        $two = Inheritance::getById($two->getId());

        $relationobjects = $two->getRelationObjects();

        $this->assertEquals(1, count($relationobjects), 'inheritance for object relations failed');
        $this->assertEquals($one->getId(), $relationobjects[0]->getId(), 'inheritance for object relations failed (wrong object)');

        /** @var Connection $db */
        $db = $this->tester->getContainer()->get('database_connection');
        $table = 'object_' . $one->getClassId();

        $relationobjectsString = $db->fetchOne(
            'SELECT relationobjects FROM ' . $table . ' WHERE oo_id = ?',
            [$two->getId()]
        );

        $this->assertEquals(
            ',' . $one->getId() . ',',
            $relationobjectsString,
            'comma separated relation ids not written correctly in object_* view'
        );
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *      | -object of other class
     *         |-two
     *
     * object relations field should inherit it's values from one to two
     */
    public function testInheritanceWithOtherClassObjectBetween(): void
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setNormalInput('parenttext');
        $one->save();

        $objectBetween = new \Pimcore\Model\DataObject\Unittest();
        $objectBetween->setParent($one);
        $objectBetween->setKey('object of other class');
        $objectBetween->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($objectBetween->getId());
        $two->setPublished(true);

        $two->setNormalInput('childtext');
        $two->save();

        $one->setRelationobjects([$one]);
        $one->save();

        Pimcore::collectGarbage();

        $two = Inheritance::getById($two->getId());

        $this->assertEquals('childtext', $two->getNormalInput(), 'inheritance failed - inherited data although child overwrote it');

        $relationobjects = $two->getRelationObjects();

        $this->assertCount(1, $relationobjects, 'inheritance for object relations failed');
        $this->assertEquals($one->getId(), $relationobjects[0]->getId(), 'inheritance for object relations failed (wrong object)');

        /** @var Connection $db */
        $db = $this->tester->getContainer()->get('database_connection');
        $table = 'object_' . $one->getClassId();

        $relationobjectsString = $db->fetchOne(
            'SELECT relationobjects FROM ' . $table . ' WHERE oo_id = ?',
            [$two->getId()]
        );

        $this->assertEquals(
            ',' . $one->getId() . ',',
            $relationobjectsString,
            'comma separated relation ids not written correctly in object_* view'
        );
    }
}
