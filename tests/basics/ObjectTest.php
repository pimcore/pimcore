<?php
namespace Pimcore\Tests;

use Pimcore\Tests\Util\TestHelper;

class ObjectTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * Verifies that a object with the same parent ID cannot be created.
     */
    public function testParentIdentical()
    {
        $savedObject = TestHelper::createEmptyObject();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        try {
            $savedObject->save();
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0()
    {
        $savedObject = TestHelper::createEmptyObject("", false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(0);
        try {
            $savedObject->save();
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }
    }
}
