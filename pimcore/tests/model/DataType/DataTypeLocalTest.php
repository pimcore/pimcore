<?php

namespace Pimcore\Tests\Model\DataType;

use Codeception\Util\Debug;
use Pimcore\Model\Object\Unittest;
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
    protected function createTestObject($fields = [])
    {
        $object = TestHelper::createEmptyObject('local', true, true);
        $this->fillObject($object, $fields);

        $object->save();

        $this->assertNotNull($object);
        $this->assertInstanceOf(Unittest::class, $object);

        $this->testObject = $object;

        return $this->testObject;
    }

    public function testObjectsWithMetadata()
    {
        $this->markTestIncomplete('To be implemented (no local comparison)');
    }

    public function testPassword()
    {
        $this->markTestIncomplete('To be checked - what is the intended behaviour here in local context?');
    }
}
