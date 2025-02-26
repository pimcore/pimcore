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

use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ObjectbrickTest
 *
 * @package Pimcore\Tests\Model\Inheritance
 *
 * @group model.inheritance.objectbrick
 */
class ObjectbrickTest extends ModelTestCase
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
     *           |-three
     *
     * add brick field value to one(parent), then add another field value to two(first level child), then add another field
     * value to three(second level child). asserts inherited and non-inherited values on children
     *
     */
    public function testInheritance(): void
    {
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        //set brick field input on one(parent)
        $myBrick = new DataObject\Objectbrick\Data\UnittestBrick($one);
        $myBrick->setBrickinput('parenttext');
        $one->getMybricks()->setUnittestBrick($myBrick);

        $one->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        //set brick field input2 on two(child)
        $two->getMybricks()->getUnittestBrick()->setBrickinput2('childtext');
        $two->save();

        $three = new Inheritance();
        $three->setKey('three');
        $three->setParentId($two->getId());
        $three->setPublished(true);

        //set brick field lazy relation on two(child)
        $brickRelations = TestHelper::createEmptyObjects('brickInheritanceRelation', true, 10);
        $three->getMybricks()->getUnittestBrick()->setBrickLazyRelation($brickRelations);
        $three->save();

        $id1 = $one->getId();
        $id2 = $two->getId();
        $id3 = $three->getId();

        $one = DataObject::getById($id1);
        $two = DataObject::getById($id2);
        $three = DataObject::getById($id3);

        //get inherited brick value from first & second level child
        $this->assertEquals('parenttext', $two->getMybricks()->getUnittestBrick()->getBrickinput());
        $this->assertEquals('parenttext', $three->getMybricks()->getUnittestBrick()->getBrickinput());

        //get child specific brick value
        $this->assertEquals('childtext', $two->getMybricks()->getUnittestBrick()->getBrickinput2());
        $this->assertEquals(10, count($three->getMybricks()->getUnittestBrick()->getBrickLazyRelation()));

        // change parent brick input value
        $one->getMybricks()->getUnittestBrick()->setBrickinput('parenttextnew');
        $one->save();

        //get inherited brick value(updated) from first & second level child
        $this->assertEquals('parenttextnew', $two->getMybricks()->getUnittestBrick()->getBrickinput());
        $this->assertEquals('parenttextnew', $three->getMybricks()->getUnittestBrick()->getBrickinput());

        // now turn inheritance off
        $class = $one->getClass();
        $class->setAllowInherit(false);
        $class->save();

        $one = DataObject::getById($id1);
        $two = DataObject::getById($id2);
        $three = DataObject::getById($id3);

        // save both objects again
        $one->save();
        $two->save();
        $three->save();

        $two = DataObject::getById($id2);
        $three = DataObject::getById($id3);

        //get inherited brick value from first & second level child
        $this->assertEquals(null, $two->getMybricks()->getUnittestBrick()->getBrickinput());
        $this->assertEquals(null, $three->getMybricks()->getUnittestBrick()->getBrickinput());

        //get child specific brick value
        $this->assertEquals('childtext', $two->getMybricks()->getUnittestBrick()->getBrickinput2());
        $this->assertEquals(10, count($three->getMybricks()->getUnittestBrick()->getBrickLazyRelation()));

        // turn it back on
        $class->setAllowInherit(true);
        $class->save();
    }
}
