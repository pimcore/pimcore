<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\Element;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Messenger\Handler\ElementDependenciesHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use RuntimeException;
use Stringable;

/**
 * Class DependenciesTest
 *
 * @package Pimcore\Tests\Model\Element
 *
 * @group model.element.dependencies
 */
class DependenciesTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function testRelation(): void
    {
        /** @var Unittest $source */
        $db = Db::get();
        $initialCount = $db->fetchOne('SELECT count(*) from dependencies');

        $source = TestHelper::createEmptyObject();
        $sourceId = $source->getId();

        $count = $db->fetchOne("SELECT count(*) from dependencies WHERE sourceType = 'object' AND sourceID = " . $sourceId);
        $this->assertEquals(0, $count);

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
    public function testObjectDependencies(): void
    {
        $source = TestHelper::createEmptyObject();

        /** @var Unittest[] $targets */
        for ($i = 0; $i <= 2; $i++) {
            $targets[] = TestHelper::createEmptyObject((string)$i);
        }
        $this->saveElementDependencies($source, $targets);

        //Reload source object
        $source = DataObject::getById($source->getId(), ['force' => true]);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertCount(3, $requires, 'DataObject: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'DataObject: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'DataObject: requiredBy dependency not saved or loaded properly');
    }

    /**
     * Verifies that a document requires and requiredBy dependencies are stored and fetched
     *
     */
    public function testDocumentDependencies(): void
    {
        $source = TestHelper::createEmptyDocumentPage();
        /** @var Unittest[] $targets */
        for ($i = 0; $i <= 2; $i++) {
            $targets[] = TestHelper::createEmptyObject((string)$i);
        }
        $this->saveElementDependencies($source, $targets);

        //Reload source document
        $source = Document::getById($source->getId(), ['force' => true]);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertCount(3, $requires, 'Document: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'Document: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'Document: requiredBy dependency not saved or loaded properly');
    }

    /**
     * Verifies that an asset requires and requiredBy dependencies are stored and fetched
     *
     */
    public function testAssetDependencies(): void
    {
        $source = TestHelper::createImageAsset();
        /** @var Unittest[] $targets */
        $targets = [];
        for ($i = 0; $i <= 2; $i++) {
            $targets[] = TestHelper::createEmptyObject((string)$i);
        }

        $this->saveElementDependencies($source, $targets);

        //Reload source asset
        $source = Asset::getById($source->getId(), ['force' => true]);

        //get dependencies
        $dependencies = $source->getDependencies();
        $requires = $dependencies->getRequires();
        $requiredBy = $dependencies->getRequiredBy();

        $this->assertCount(3, $requires, 'Asset: requires dependencies not saved or loaded properly');
        $this->assertEquals($targets[0]->getId(), $requires[0]['id'], 'Asset: requires dependency not saved or loaded properly');
        $this->assertEquals($targets[2]->getId(), $requiredBy[0]['id'], 'Asset: requiredBy dependency not saved or loaded properly');
    }

    /**
     * Regression test for #14699: moving a document changes the properties its descendants inherit, so their
     * dependencies have to be resolved again, not only the ones of the moved document.
     */
    public function testMovingDocumentUpdatesDependenciesOfDescendants(): void
    {
        $snippet = new Document\Snippet();
        $snippet->setParentId(1);
        $snippet->setKey('snippet' . uniqid());
        $snippet->setPublished(true);
        $snippet->save();

        $source = TestHelper::createDocumentFolder();
        $target = TestHelper::createDocumentFolder();

        $property = new Property();
        $property->setName('sidebar');
        $property->setType('document');
        $property->setData($snippet);
        $property->setInherited(false);
        $property->setInheritable(true);
        $source->setProperties(['sidebar' => $property]);
        $source->save();

        $folder = new Document\Folder();
        $folder->setParentId($source->getId());
        $folder->setKey('moved');
        $folder->save();

        $child = new Document\Page();
        $child->setParentId($folder->getId());
        $child->setKey('child');
        $child->setPublished(true);
        $child->save();

        $requiredBy = static fn (): array => array_column(
            Document::getById($snippet->getId(), ['force' => true])->getDependencies()->getRequiredBy(),
            'id'
        );
        $this->assertContains($child->getId(), $requiredBy(), 'The child inherits the property before the move');

        $folder = Document::getById($folder->getId(), ['force' => true]);
        $folder->setParentId($target->getId());
        $folder->save();

        $this->assertNull(
            Document::getById($child->getId(), ['force' => true])->getProperty('sidebar'),
            'The child does not inherit the property any more'
        );
        $this->assertNotContains($child->getId(), $requiredBy(), 'The dependency of the child on the snippet is stale after the move');
    }

    /**
     * @param Concrete[] $targets
     */
    private function saveElementDependencies(ElementInterface $source, array $targets): void
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

    /**
     * Regression test: the dependencies handler shows unpublished elements and switches off the inherited values
     * while it resolves the dependencies. A failure must restore both modes, the handler runs in long-lived
     * messenger workers.
     */
    public function testDependenciesHandlerRestoresStaticModesOnFailure(): void
    {
        $object = new class() extends Unittest {
            public function resolveDependencies(): array
            {
                throw new RuntimeException('resolving dependencies failed');
            }
        };
        $object->setId(PHP_INT_MAX);
        RuntimeCache::set('object_' . PHP_INT_MAX, $object);

        $getInheritedValues = DataObject::getGetInheritedValues();
        $hideUnpublished = DataObject::getHideUnpublished();
        DataObject::setGetInheritedValues(true);
        DataObject::setHideUnpublished(true);

        try {
            (new ElementDependenciesHandler(new NullLogger()))(new ElementDependenciesMessage('object', PHP_INT_MAX));
            $this->fail('The handler must pass on the failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('resolving dependencies failed', $e->getMessage());
        } finally {
            $restoredGetInheritedValues = DataObject::getGetInheritedValues();
            $restoredHideUnpublished = DataObject::getHideUnpublished();
            DataObject::setGetInheritedValues($getInheritedValues);
            DataObject::setHideUnpublished($hideUnpublished);
            RuntimeCache::set('object_' . PHP_INT_MAX, null);
        }

        $this->assertTrue($restoredGetInheritedValues);
        $this->assertTrue($restoredHideUnpublished);
    }

    /**
     * Regression test: the modes are restored for any failure after they were switched, not only for one of
     * resolveDependencies().
     */
    public function testDependenciesHandlerRestoresStaticModesWhenLoggingFails(): void
    {
        $object = TestHelper::createEmptyObject();
        $logger = new class() extends AbstractLogger {
            public function log($level, string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('logging failed');
            }
        };

        $getInheritedValues = DataObject::getGetInheritedValues();
        $hideUnpublished = DataObject::getHideUnpublished();
        DataObject::setGetInheritedValues(true);
        DataObject::setHideUnpublished(true);

        try {
            (new ElementDependenciesHandler($logger))(new ElementDependenciesMessage('object', $object->getId()));
            $this->fail('The handler must pass on the failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('logging failed', $e->getMessage());
        } finally {
            $restoredGetInheritedValues = DataObject::getGetInheritedValues();
            $restoredHideUnpublished = DataObject::getHideUnpublished();
            DataObject::setGetInheritedValues($getInheritedValues);
            DataObject::setHideUnpublished($hideUnpublished);
        }

        $this->assertTrue($restoredGetInheritedValues);
        $this->assertTrue($restoredHideUnpublished);
    }
}
