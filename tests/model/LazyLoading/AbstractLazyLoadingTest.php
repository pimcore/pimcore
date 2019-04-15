<?php

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class AbstractLazyLoadingTest extends ModelTestCase
{
    const RELATION_COUNT = 5;

    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createRelationObjects();
    }

    public function tearDown()
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses()
    {
        $name = 'RelationTest';
        $file = 'relations/class_RelationTest_export.json';
        $class = ClassDefinition::getByName($name);

        if (!$class) {
            /** @var ClassDefinition $class */
            $class = $this->tester->setupClass($name, $file);
        }

        $name = 'LazyLoadingTest';
        $file = 'lazyloading/fieldcollection_LazyLoadingTest_export.json';
        $fieldCollection = $this->tester->setupFieldCollection($name, $file);

        $name = 'LazyLoadingLocalizedTest';
        $file = 'lazyloading/fieldcollection_LazyLoadingLocalizedTest_export.json';
        $fieldCollection = $this->tester->setupFieldCollection($name, $file);

        $name = 'LazyLoading';
        $file = 'lazyloading/class_LazyLoading_export.json';
        $class = ClassDefinition::getByName($name);

        if (!$class) {
            /** @var ClassDefinition $class */
            $class = $this->tester->setupClass($name, $file);
        }

        $name = 'LazyLoadingTest';
        $file = 'lazyloading/objectbrick_LazyLoadingTest_export.json';
        $brick = $this->tester->setupObjectBrick($name, $file);

        $name = 'LazyLoadingLocalizedTest';
        $file = 'lazyloading/objectbrick_LazyLoadingLocalizedTest_export.json';
        $brick = $this->tester->setupObjectBrick($name, $file);
    }

    protected function createRelationObjects()
    {
        for ($i = 0; $i < 20; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath('__test/relationobjects'));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->setSomeAttribute("Some content $i");
            $object->save();
        }
    }

    /**
     * @return LazyLoading
     */
    protected function createDataObject(): LazyLoading
    {
        $object = new LazyLoading();
        $object->setParentId(1);
        $object->setKey('lazy1');
        $object->setPublished(true);

        return $object;
    }

    /**
     * @param $parent
     *
     * @return LazyLoading
     *
     * @throws \Exception
     */
    protected function createChildDataObject($parent): LazyLoading
    {
        $object = new LazyLoading();
        $object->setParent($parent);
        $object->setKey('sub-lazy');
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    /**
     * @return RelationTest\Listing
     *
     * @throws \Exception
     */
    protected function loadRelations(): RelationTest\Listing
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(self::RELATION_COUNT);

        return $listing;
    }

    protected function loadSingleRelation(): RelationTest
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(1);

        return $listing->load()[0];
    }

    protected function checkSerialization(LazyLoading $object, string $messagePrefix, bool $contentShouldBeIncluded = false)
    {
        $serializedString = serialize($object);
        $this->checkSerializedStringForNeedle($serializedString, ['lazyLoadedFields', 'lazyKeys', 'loadedLazyKeys'], false, $messagePrefix);
        $this->checkSerializedStringForNeedle($serializedString, 'someAttribute";s:14:"Some content', $contentShouldBeIncluded, $messagePrefix);
    }

    protected function checkSerializedStringForNeedle(string $string, $needle, bool $expected, string $messagePrefix = null)
    {
        if (!is_array($needle)) {
            $needle = [$needle];
        }

        foreach ($needle as $item) {
            $this->assertEquals($expected, strpos($string, $item) !== false, $messagePrefix . "Check if '$item' is occuring in serialized data.");
        }
    }
}
