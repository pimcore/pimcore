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
use Pimcore\Model\DataObject\Csstore;
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

    private const RELATION_KEY_PREFIX = 'cs-relation-dependency-';

    private const OBJECT_TYPE = 'object';

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
}
