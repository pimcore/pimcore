<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class ClassTest extends RestTestCase
{
    public function testGetClass()
    {
        $object = TestHelper::createEmptyObject();
        $classId = $object->getClassId();

        $this->assertEquals('unittest', ClassDefinition::getById($classId)->getName());

        $restClass1 = $this->restClient->getClassById($classId);
        $this->assertEquals('unittest', $restClass1->getName());

        $restClass2 = $this->restClient->getObjectMetaById($object->getId());
        $this->assertEquals('unittest', $restClass2->getName());
    }
}
