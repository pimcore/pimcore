<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Tests\RestTester;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class ClassTest extends RestTestCase
{
    /**
     * @var RestTester
     */
    protected $tester;

    /**
     * @var RestClient
     */
    protected $restClient;

    public function setUp()
    {
        parent::setUp();

        // every single rest test assumes a clean database
        TestHelper::cleanUp();

        // authenticate as rest user
        $this->tester->addApiKeyParam('rest');

        // setup test rest client
        $this->restClient = new RestClient($this->tester);
    }

    public function testGetClass()
    {
        $object = TestHelper::createEmptyObject();
        $classId = $object->getClassId();

        $this->assertEquals("unittest", ClassDefinition::getById($classId)->getName());

        $restClass1 = $this->restClient->getClassById($classId);
        $this->assertEquals("unittest", $restClass1->getName());

        $restClass2 = $this->restClient->getObjectMetaById($object->getId());
        $this->assertEquals("unittest", $restClass2->getName());
    }
}
