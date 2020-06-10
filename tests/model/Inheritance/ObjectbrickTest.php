<?php

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class ObjectbrickTest
 *
 * @package Pimcore\Tests\Model\Inheritance
 * @group model.inheritance.objectbrick
 */
class ObjectbrickTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();
        \Pimcore::setAdminMode();
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
     * value to three(second level child). asserts inherited and non-inherited values on childs
     *
     */
    public function testInheritance()
    {
        /** @var Inheritance $one */
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        //set brick field input on one(parent)
        $myBrick = new DataObject\Objectbrick\Data\UnittestBrick($one);
        $myBrick->setBrickinput('parenttext');
        $one->getMybricks()->setUnittestBrick($myBrick);

        $one->save();

        /** @var Inheritance $two */
        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        //set brick field input2 on two(child)
        $two->getMybricks()->getUnittestBrick()->setBrickinput2('childtext');
        $two->save();

        /** @var Inheritance $three */
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

        $one = AbstractObject::getById($id1);
        $two = AbstractObject::getById($id2);
        $three = AbstractObject::getById($id3);

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

        $one = AbstractObject::getById($id1);
        $two = AbstractObject::getById($id2);
        $three = AbstractObject::getById($id3);

        // save both objects again
        $one->save();
        $two->save();
        $three->save();

        $two = AbstractObject::getById($id2);
        $three = AbstractObject::getById($id3);

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
