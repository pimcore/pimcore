<?php

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Test\DataType\AbstractDataTypeTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * @group dataTypeLocal
 */
class DataTypeTest extends AbstractDataTypeTestCase
{
    /**
     * Creates and saves object locally without testing against a comparison object
     *
     * @inheritDoc
     */
    protected function createTestObject($fields = [], &$returnData = [])
    {
        $object = TestHelper::createEmptyObject('local', true, true);
        if ($fields) {
            $this->fillObject($object, $fields, $returnData);
        }

        $object->save();

        $this->assertNotNull($object);
        $this->assertInstanceOf(Unittest::class, $object);

        $this->testObject = $object;

        return $this->testObject;
    }

    public function refreshObject() {
        $this->testObject = AbstractObject::getById($this->testObject->getId(), true);
    }
}
