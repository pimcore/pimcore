<?php

namespace Pimcore\Tests\Rest\DataType;

use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Unittest;
use Pimcore\Tests\Test\DataType\AbstractDataTypeRestTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * @group dataTypeIn
 */
class DataTypeInTest extends AbstractDataTypeRestTestCase
{
    /**
     * Creates unsaved object locally, saves it via API and loads comparison object directly
     *
     * @inheritDoc
     */
    protected function createTestObject($fields = [])
    {
        $object = TestHelper::createEmptyObject('local', false, true);
        $this->fillObject($object, $fields);

        $response = $this->restClient->createObjectConcrete($object);

        $this->assertTrue($response->success);

        /** @var Unittest $localObject */
        $localObject = AbstractObject::getById($response->id);

        $this->assertNotNull($localObject);
        $this->assertInstanceOf(Unittest::class, $localObject);

        $this->testObject       = $localObject;
        $this->comparisonObject = $object;

        return $this->testObject;
    }
}
