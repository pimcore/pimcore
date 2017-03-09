<?php

namespace Pimcore\Tests\Rest\DataType;

use Codeception\Util\Debug;
use Pimcore\Model\Object\Unittest;
use Pimcore\Tests\Util\TestHelper;

/**
 * @group dataTypeOut
 */
class DataTypeOutTest extends AbstractDataTypeTestCase
{
    /**
     * @inheritDoc
     */
    protected function createTestObject($fields = [])
    {
        Debug::debug('CREATING TEST OBJECT: ' . json_encode($fields, true));

        $object = TestHelper::createEmptyObject('local', true, true);
        $this->fillObject($object, $fields);

        /** @var Unittest $restObject */
        $restObject = $this->restClient->getObjectById($object->getId());

        $this->assertNotNull($restObject);
        $this->assertInstanceOf(Unittest::class, $restObject);

        $this->testObject       = $restObject;
        $this->comparisonObject = $object;

        Debug::debug('TEST OBJECT: ' . $this->testObject->getId());
        Debug::debug('COMPARISON OBJECT: ' . $this->comparisonObject->getId());

        return $this->testObject;
    }
}
