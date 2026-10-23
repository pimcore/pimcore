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

use Pimcore;
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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

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
        [$snippet, $source, $folder, $child] = $this->createInheritingDocumentTree();
        $target = TestHelper::createDocumentFolder();

        $this->assertContains($child->getId(), $this->getRequiredByIds($snippet), 'The child inherits the property before the move');

        $folder->setParentId($target->getId());
        $folder->save();

        $this->assertNull(
            Document::getById($child->getId(), ['force' => true])->getProperty('sidebar'),
            'The child does not inherit the property any more'
        );
        $this->assertNotContains($child->getId(), $this->getRequiredByIds($snippet), 'The dependency of the child on the snippet is stale after the move');
    }

    public function testMovingDocumentDoesNotQueueSiblingsSharingTheNamePrefix(): void
    {
        [$snippet, $source, $folder] = $this->createInheritingDocumentTree();
        $target = TestHelper::createDocumentFolder();

        $sibling = new Document\Folder();
        $sibling->setParentId($source->getId());
        $sibling->setKey($folder->getKey() . '-sibling');
        $sibling->save();

        $siblingChild = new Document\Page();
        $siblingChild->setParentId($sibling->getId());
        $siblingChild->setKey('child');
        $siblingChild->setPublished(true);
        $siblingChild->save();

        $this->assertContains($siblingChild->getId(), $this->getRequiredByIds($snippet));

        // the sibling is not moved, so nothing may refresh its dependencies: drop them to detect a refresh
        Db::get()->delete('dependencies', ['sourcetype' => 'document', 'sourceid' => $siblingChild->getId()]);

        $folder->setParentId($target->getId());
        $folder->save();

        $this->assertNotContains($siblingChild->getId(), $this->getRequiredByIds($snippet), 'The sibling must not be queued');
    }

    public function testRenamingDocumentDoesNotQueueDescendants(): void
    {
        [$snippet, , $folder, $child] = $this->createInheritingDocumentTree();

        $this->assertContains($child->getId(), $this->getRequiredByIds($snippet));

        // a rename keeps the inherited properties, so nothing may refresh the descendants: drop their dependencies to detect a refresh
        Db::get()->delete('dependencies', ['sourcetype' => 'document', 'sourceid' => $child->getId()]);

        $folder->setKey($folder->getKey() . '-renamed');
        $folder->save();

        $this->assertNotContains($child->getId(), $this->getRequiredByIds($snippet), 'A rename must not queue the descendants');
    }

    /**
     * A failing dependency update of one descendant must neither abort the move nor keep the other descendants
     * from being refreshed (queueing it fails).
     */
    public function testMovingDocumentSurvivesFailingDescendant(): void
    {
        [$snippet, , $folder, $child] = $this->createInheritingDocumentTree();
        $target = TestHelper::createDocumentFolder();

        $second = new Document\Page();
        $second->setParentId($folder->getId());
        $second->setKey('second');
        $second->setPublished(true);
        $second->save();

        $this->assertContains($child->getId(), $this->getRequiredByIds($snippet));
        $this->assertContains($second->getId(), $this->getRequiredByIds($snippet));

        $container = Pimcore::getContainer();
        $bus = $container->get('messenger.bus.pimcore-core');
        $failingId = $child->getId();
        $container->set('messenger.bus.pimcore-core', new class($bus, $failingId) implements MessageBusInterface {
            public function __construct(private MessageBusInterface $bus, private int $failingId)
            {
            }

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                if ($message instanceof ElementDependenciesMessage && $message->getId() === $this->failingId) {
                    throw new RuntimeException('queueing failed');
                }

                return $this->bus->dispatch($message, $stamps);
            }
        });

        try {
            $folder->setParentId($target->getId());
            $folder->save();
        } finally {
            $container->set('messenger.bus.pimcore-core', $bus);
        }

        $this->assertSame($target->getId(), Document::getById($folder->getId(), ['force' => true])->getParentId(), 'The move is committed');
        $requiredBy = $this->getRequiredByIds($snippet);
        $this->assertNotContains($second->getId(), $requiredBy, 'The other descendant is still refreshed');
        $this->assertContains($child->getId(), $requiredBy, 'The failing descendant keeps its dependencies');
    }

    /**
     * @return array{Document\Snippet, Document, Document\Folder, Document\Page} snippet, property holder, moved folder, its child
     */
    private function createInheritingDocumentTree(): array
    {
        $snippet = new Document\Snippet();
        $snippet->setParentId(1);
        $snippet->setKey('snippet' . uniqid());
        $snippet->setPublished(true);
        $snippet->save();

        $source = TestHelper::createDocumentFolder();

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
        $folder->setKey('moved' . uniqid());
        $folder->save();

        $child = new Document\Page();
        $child->setParentId($folder->getId());
        $child->setKey('child');
        $child->setPublished(true);
        $child->save();

        return [$snippet, $source, $folder, $child];
    }

    /**
     * @return int[]
     */
    private function getRequiredByIds(Document $document): array
    {
        return array_column(
            Document::getById($document->getId(), ['force' => true])->getDependencies()->getRequiredBy(),
            'id'
        );
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
