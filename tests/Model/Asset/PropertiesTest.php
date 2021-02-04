<?php

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
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
        $this->testElement = TestHelper::createAssetFolder();
        $this->testElement->save();

        $this->assertNotNull($this->testElement);
        $this->assertInstanceOf(Asset\Folder::class, $this->testElement);
        return $this->testElement;

    }

    public function reloadElement(): ElementInterface
    {
        $this->testElement = Asset::getById($this->testElement->getId(), true);
        return $this->testElement;
    }

}
