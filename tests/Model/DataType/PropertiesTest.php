<?php

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Test\AbstractPropertiesTest;
use Pimcore\Tests\Test\DataType\AbstractDataTypeTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * @group properties
 */
class PropertiesTest extends AbstractPropertiesTest
{
    public function createElement(): ElementInterface
    {
        $this->testElement = TestHelper::createEmptyObject('local', true, true, '\\Pimcore\\Model\\DataObject\\Inheritance');
        $this->testElement->save();

        $this->assertNotNull($this->testElement);
        $this->assertInstanceOf(Inheritance::class, $this->testElement);
        return $this->testElement;

    }

    public function reloadElement(): ElementInterface
    {
        $this->testElement = AbstractObject::getById($this->testElement->getId(), true);
        return $this->testElement;
    }

}
