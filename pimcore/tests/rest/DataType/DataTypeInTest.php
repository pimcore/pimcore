<?php

namespace Pimcore\Tests\Rest\DataType;

use Codeception\Util\Debug;
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
     * @inheritDoc
     */
    protected function createTestObject($fields = [])
    {
        Debug::debug('CREATING TEST OBJECT: ' . json_encode($fields, true));

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

        Debug::debug('TEST OBJECT: ' . $this->testObject->getId());
        Debug::debug('COMPARISON OBJECT: ' . json_encode($response, true));

        return $this->testObject;
    }
}
