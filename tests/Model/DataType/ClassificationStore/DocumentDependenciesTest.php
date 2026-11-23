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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Messenger\Handler\ElementDependenciesHandler;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\Dependency;
use Pimcore\Model\Element\Service;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * DependenciesTest against a store on the document engine: the dependencies and cache tags of embedded elements
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * A relation-typed key adds the case the other types do not have: its stored value is a descriptor
 * until the key is read, so the dependency rows have to come off the descriptor - otherwise an
 * ordinary save of an object nobody read the key of would wipe them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentDependenciesTest extends DependenciesTest
{
    use RelationFixtureTrait;

    private const STORE_NAME = 'teststore';

    private const CLASS_NAME = 'csstore';

    private const OBJECT_TYPE = 'object';

    private const ASSET_TYPE = 'asset';

    private const DOCUMENT_TYPE = 'document';

    private const KEY_PREFIX = 'cs-structured-dependency-';

    private const RELATION_KEY_PREFIX = 'cs-relation-dependency-';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    protected function tearDown(): void
    {
        $this->deleteRelationFixture();

        parent::tearDown();
    }

    public function testSavingWritesOneDependencyRowPerTargetOfARelationKey(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $first = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'first');
        $second = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'second');

        $object = $this->createRelationObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$first, $second]);
        $object->save();

        $this->saveDependencyRowsOf($object);

        $this->assertSame(1, $this->countDependencyRowsOf($object->getId(), $first->getId()));
        $this->assertSame(1, $this->countDependencyRowsOf($object->getId(), $second->getId()));
    }

    /**
     * The descriptor is what the save sees, and it names the targets - so the rows survive a save
     * that never resolved one of them.
     */
    public function testTheDependencyRowsSurviveASaveThatNeverReadTheRelationKey(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'kept');

        $object = $this->createRelationObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$target]);
        $object->save();

        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->saveDependencyRowsOf($reloaded);

        $this->assertSame(1, $this->countDependencyRowsOf($object->getId(), $target->getId()));

        $requiredBy = Service::getRequiredByDependenciesForFrontend(
            Dependency::getBySourceId($target->getId(), self::OBJECT_TYPE),
            null,
            null
        );

        $this->assertContains($object->getId(), array_column($requiredBy['requiredBy'], 'id'));
    }

    public function testClearingARelationKeyRemovesItsDependencyRows(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'cleared');

        $object = $this->createRelationObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$target]);
        $object->save();
        $this->saveDependencyRowsOf($object);

        $this->assertSame(1, $this->countDependencyRowsOf($object->getId(), $target->getId()));

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, null);
        $reloaded->save();
        $this->saveDependencyRowsOf($reloaded);

        $this->assertSame(0, $this->countDependencyRowsOf($object->getId(), $target->getId()));
    }

    private function createRelationObject(): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::RELATION_KEY_PREFIX . 'owner');
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    private function saveDependencyRowsOf(Csstore $object): void
    {
        (new ElementDependenciesHandler(new NullLogger()))(
            new ElementDependenciesMessage(self::OBJECT_TYPE, $object->getId())
        );
    }

    private function countDependencyRowsOf(int $objectId, int $targetId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT count(*) FROM dependencies WHERE sourceType = ? AND sourceId = ?'
            . ' AND targetType = ? AND targetId = ?',
            [self::OBJECT_TYPE, $objectId, self::OBJECT_TYPE, $targetId]
        );
    }

    /**
     * A `link` key pointing at a document and a `video` key pointing at an asset are reached by the
     * walk the classification store data type already carries - resolveDependencies() and
     * getCacheTags() on {@see ClassificationstoreField} - so the two types that are new to a store
     * register their dependency and contribute their cache tag with no walk of their own.
     *
     * Both keys sit in one object, because the walk is one pass over the store's own stored values:
     * a pass that reached only the first of them would pass a test that wrote one.
     */
    public function testALinkKeyAndAVideoKeyRegisterTheirDependenciesAndCacheTags(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);

        try {
            $document = TestHelper::createEmptyDocumentPage(self::KEY_PREFIX);
            $asset = TestHelper::createVideoAsset(self::KEY_PREFIX);

            $link = new Link();
            $link->setText('the page');
            $link->setLinktype('internal');
            $link->setInternalType(self::DOCUMENT_TYPE);
            $link->setInternal($document->getId());

            $video = new Video();
            $video->setType(self::ASSET_TYPE);
            $video->setData($asset);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'object');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $this->keyId($store, 'link'), $link);
            $csField->setLocalizedKeyValue($group->getId(), $this->keyId($store, 'video'), $video);
            $object->save();

            $dependencies = $this->getCsstoreFieldDefinition()->resolveDependencies($object->getCsstore());
            $this->assertContains(['id' => $document->getId(), 'type' => self::DOCUMENT_TYPE], $dependencies);
            $this->assertContains(['id' => $asset->getId(), 'type' => self::ASSET_TYPE], $dependencies);

            $tags = $object->getCacheTags();
            $this->assertArrayHasKey(Service::getElementCacheTag(self::DOCUMENT_TYPE, $document->getId()), $tags);
            $this->assertArrayHasKey(Service::getElementCacheTag(self::ASSET_TYPE, $asset->getId()), $tags);

            (new ElementDependenciesHandler(new NullLogger()))(
                new ElementDependenciesMessage(self::OBJECT_TYPE, $object->getId())
            );

            $this->assertSame(
                1,
                $this->countDependencyRows($object->getId(), self::DOCUMENT_TYPE, $document->getId())
            );
            $this->assertSame(
                1,
                $this->countDependencyRows($object->getId(), self::ASSET_TYPE, $asset->getId())
            );
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    private function keyId(Classificationstore\StoreConfig $store, string $keyName): int
    {
        $key = Classificationstore\KeyConfig::getByName($keyName, $store->getId());
        $this->assertNotNull($key, $keyName . ' has to be defined');

        return (int) $key->getId();
    }

    private function countDependencyRows(int $objectId, string $targetType, int $targetId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT count(*) FROM dependencies WHERE sourceType = ? AND sourceId = ?'
            . ' AND targetType = ? AND targetId = ?',
            [self::OBJECT_TYPE, $objectId, $targetType, $targetId]
        );
    }

    private function getCsstoreFieldDefinition(): ClassificationstoreField
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertNotNull($class);

        $fieldDefinition = $class->getFieldDefinition(self::CLASS_NAME);
        $this->assertInstanceOf(ClassificationstoreField::class, $fieldDefinition);

        return $fieldDefinition;
    }
}
