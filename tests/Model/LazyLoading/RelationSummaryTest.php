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

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\LightweightRelationLoadingInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Element;
use Pimcore\Tests\Support\Util\TestHelper;

class RelationSummaryTest extends AbstractLazyLoadingTest
{
    public function testObjectRelationSummariesMatchTheGetterWithoutLoadingTheRelatedObjects(): void
    {
        $object = $this->createDataObject();
        $object->setObjects($this->loadRelations()->load());
        $object->save();
        $expected = array_map(static fn (RelationTest $related) => $related->getId(), $object->getObjects());

        $object = $this->reload($object);
        $field = $this->getField($object, 'objects');

        $summaries = $field->loadRelationSummaries($object);

        $this->assertSame($expected, array_column($summaries, 'id'), 'relation order must be kept');
        $this->assertFalse($object->isLazyKeyLoaded('objects'), 'the field must not be marked as loaded');
        foreach ($expected as $id) {
            $this->assertFalse(RuntimeCache::isRegistered(Element\Service::getElementCacheTag('object', $id)), 'related object was instantiated');
        }

        // the regular getter describes the very same elements
        $related = $object->getObjects();
        foreach ($summaries as $i => $summary) {
            $this->assertSame($related[$i]->getId(), $summary['id']);
            $this->assertSame('object', $summary['type']);
            $this->assertSame($related[$i]->getParentId(), $summary['parentId']);
            $this->assertSame($related[$i]->getType(), $summary['subtype']);
            $this->assertSame($related[$i]->getClassName(), $summary['className']);
            $this->assertSame($related[$i]->getRealFullPath(), $summary['fullpath']);
            $this->assertSame($related[$i]->getKey(), $summary['filename']);
            $this->assertSame($related[$i]->isPublished(), $summary['published']);
        }
    }

    public function testNoRelationsYieldAnEmptyList(): void
    {
        $object = $this->createDataObject();
        $object->save();

        $object = $this->reload($object);

        $this->assertSame([], $this->getField($object, 'objects')->loadRelationSummaries($object));
    }

    public function testUnpublishedRelationsAreHiddenLikeInTheGetter(): void
    {
        $preservedState = Concrete::getHideUnpublished();

        $unpublished = new RelationTest();
        $unpublished->setParent(Service::createFolderByPath('/rel-summary'));
        $unpublished->setPublished(false);
        $unpublished->setKey('unpublished');
        $unpublished->save();

        $object = $this->createDataObject();
        $object->setObjects([$unpublished, $this->loadSingleRelation()]);
        $object->save();

        try {
            Concrete::setHideUnpublished(true);
            $object = $this->reload($object);
            $this->assertCount(1, $this->getField($object, 'objects')->loadRelationSummaries($object));
            $this->assertCount(1, $object->getObjects());

            Concrete::setHideUnpublished(false);
            $object = $this->reload($object);
            $summaries = $this->getField($object, 'objects')->loadRelationSummaries($object);
            $this->assertCount(2, $summaries);
            $this->assertFalse($summaries[0]['published']);
            $this->assertTrue($summaries[1]['published']);
        } finally {
            Concrete::setHideUnpublished($preservedState);
        }
    }

    public function testMixedElementRelationSummaries(): void
    {
        $asset = TestHelper::createImageAsset();
        $document = TestHelper::createEmptyDocumentPage();
        $related = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $object->setRelations([$related]);
        $object->save();

        // the test class only allows objects; add asset and document relations on the persistence level
        $db = Db::get();
        $table = 'object_relations_' . $object->getClassId();
        foreach ([[$asset->getId(), 'asset', 1], [$document->getId(), 'document', 2], [999999999, 'object', 3]] as [$id, $type, $index]) {
            $db->insert($table, [
                'src_id' => $object->getId(),
                'dest_id' => $id,
                'type' => $type,
                'fieldname' => 'relations',
                'index' => $index,
                'ownertype' => 'object',
                'ownername' => '',
                'position' => '0',
            ]);
        }

        $object = $this->reload($object);
        $summaries = $this->getField($object, 'relations')->loadRelationSummaries($object);

        // the id of the relation to a vanished element is skipped
        $this->assertCount(3, $summaries);
        $this->assertSame(['object', 'asset', 'document'], array_column($summaries, 'type'));
        $this->assertSame([$related->getId(), $asset->getId(), $document->getId()], array_column($summaries, 'id'));

        [, $assetSummary, $documentSummary] = $summaries;
        $this->assertSame('image', $assetSummary['subtype']);
        $this->assertNull($assetSummary['className']);
        $this->assertSame($asset->getParentId(), $assetSummary['parentId']);
        $this->assertSame($asset->getRealFullPath(), $assetSummary['fullpath']);
        $this->assertSame($asset->getFilename(), $assetSummary['filename']);
        $this->assertTrue($assetSummary['published']);
        $this->assertSame('page', $documentSummary['subtype']);
        $this->assertSame($document->getParentId(), $documentSummary['parentId']);
        $this->assertSame($document->getRealFullPath(), $documentSummary['fullpath']);
        $this->assertSame($document->isPublished(), $documentSummary['published']);
    }

    public function testAdvancedRelationsDescribeTheirTargetsToo(): void
    {
        $object = $this->createDataObject();
        $related = $this->loadSingleRelation();
        $metadata = new Pimcore\Model\DataObject\Data\ObjectMetadata('advancedObjects', ['metadataUpper'], $related);
        $metadata->setMetadataUpper('meta');
        $object->setAdvancedObjects([$metadata]);
        $object->save();

        $object = $this->reload($object);
        $summaries = $this->getField($object, 'advancedObjects')->loadRelationSummaries($object);

        $this->assertSame([$related->getId()], array_column($summaries, 'id'));
    }

    protected function reload(LazyLoading $object): LazyLoading
    {
        Cache::clearAll();
        Pimcore::collectGarbage();

        return LazyLoading::getById($object->getId(), ['force' => true]);
    }

    protected function getField(LazyLoading $object, string $name): LightweightRelationLoadingInterface
    {
        $field = $object->getClass()->getFieldDefinition($name);
        $this->assertInstanceOf(LightweightRelationLoadingInterface::class, $field);

        return $field;
    }
}
