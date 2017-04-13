<?php

namespace Pimcore\Tests\Model\Object;

use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class ObjectTest extends ModelTestCase
{
    /**
     * Verifies that a object with the same parent ID cannot be created.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage ParentID and ID is identical, an element can't be the parent of itself.
     */
    public function testParentIdentical()
    {
        $savedObject = TestHelper::createEmptyObject();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be 0
     *
     * @expectedException \Exception
     * @expectedExceptionMessage ParentID and ID is identical, an element can't be the parent of itself.
     */
    public function testParentIs0()
    {
        $savedObject = TestHelper::createEmptyObject('', false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(0);
        $savedObject->save();
    }
}
