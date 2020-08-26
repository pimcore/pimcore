<?php

namespace Pimcore\Tests\Model\Element;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class DependenciesTest
 *
 * @package Pimcore\Tests\Model\Element
 * @group model.element.dependencies
 */
class DependenciesTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function testRelation()
    {
        /** @var Unittest $source */
        $db = Db::get();
        $initialCount = $db->fetchOne('SELECT count(*) from dependencies');

        $source = TestHelper::createEmptyObject();
        $sourceId = $source->getId();

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' AND sourceID = " . $sourceId);
        $this->assertEquals(0, $count);

        /** @var Unittest[] $targets */
        $targets = TestHelper::createEmptyObjects('', true, 5);
        $source->setMultihref([$targets[0], $targets[1]]);
        $source->save();

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' AND sourceID = " . $sourceId);
        $this->assertEquals(2, $count);

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' "
            . ' AND sourceID = ' . $sourceId . " AND targetType = 'object' AND targetId = " . $targets[1]->getId());
        $this->assertEquals(1, $count);

        $source->setMultihref([$targets[0], $targets[3], $targets[4]]);
        $source->save();

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' "
            . ' AND sourceID = ' . $sourceId . " AND targetType = 'object' AND targetId = " . $targets[1]->getId());
        $this->assertEquals(0, $count);

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' AND sourceID = " . $sourceId);
        $this->assertEquals(3, $count);

        $finalCount = $db->fetchOne('SELECT count(*) from dependencies');
        $this->assertEquals($initialCount + 3, $finalCount);

        $source->delete();
        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' AND sourceID = " . $sourceId);
        $this->assertEquals(0, $count);
    }

    /**
     * Verifies that an object requires and requiredBy dependencies are stored and fetched
     *
     */
    public function testObjectDependencies()
    {
        /** @var DataObject $source */
        $source = TestHelper::createEmptyObject();

        /** @var Unittest[] $targets */
        $targets = TestHelper::createEmptyObjects('', true, 3);
        $this->saveElementDependencies($source, $targets);

        //Reload source object
        $source = AbstractObject::getById($source->getId(), true);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertEquals(3, count($requires), 'DataObject: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'DataObject: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'DataObject: requiredBy dependency not saved or loaded properly');
    }

    /**
     * Verifies that a document requires and requiredBy dependencies are stored and fetched
     *
     */
    public function testDocumentDependencies()
    {
        /** @var Document $source */
        $source = TestHelper::createEmptyDocumentPage();
        /** @var Unittest[] $targets */
        $targets = TestHelper::createEmptyObjects('', true, 3);
        $this->saveElementDependencies($source, $targets);

        //Reload source document
        $source = Document::getById($source->getId(), true);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertEquals(3, count($requires), 'Document: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'Document: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'Document: requiredBy dependency not saved or loaded properly');
    }

    /**
     * Verifies that an asset requires and requiredBy dependencies are stored and fetched
     *
     */
    public function testAssetDependencies()
    {
        /** @var Asset $source */
        $source = TestHelper::createImageAsset();
        /** @var Unittest[] $targets */
        $targets = TestHelper::createEmptyObjects('', true, 3);
        $this->saveElementDependencies($source, $targets);

        //Reload source asset
        $source = Asset::getById($source->getId(), true);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertEquals(3, count($requires), 'Asset: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'Asset: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'Asset: requiredBy dependency not saved or loaded properly');
    }

    /**
     * @param $source
     * @param $targets
     *
     */
    private function saveElementDependencies($source, $targets)
    {
        $properties = [];
        foreach ($targets as $idx => $target) {
            $propertyName = 'prop_' . $idx;
            $property = new Property();
            $property->setType('object');
            $property->setName($propertyName);
            $property->setCtype('object');
            $property->setDataFromEditmode($target);
            $properties[$propertyName] = $property;
        }

        $source->setProperties($properties);
        $source->save();

        $targets[2]->setMultihref([$source]);
        $targets[2]->save();
    }
}
