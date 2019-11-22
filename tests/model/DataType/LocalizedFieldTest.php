<?php

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class LocalizedFieldTest extends ModelTestCase
{
    public function tearDown()
    {
        Localizedfield::setStrictMode(Localizedfield::STRICT_DISABLED);
    }

    public function testStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        $object->setLinput('Test');
        $this->assertEquals('Test', $object->getLinput());

        $object->setLinput('TestKo', 'ko');
        $this->assertEquals('TestKo', $object->getLinput('ko'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Language  not accepted in strict mode
     */
    public function testExceptionInStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $object->setLinput('Test');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Language ko not accepted in strict mode
     */
    public function testExceptionWithLocaleInStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $object->setLinput('Test', 'ko');
    }
}
