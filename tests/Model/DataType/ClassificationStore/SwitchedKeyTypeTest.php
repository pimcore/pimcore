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

use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * What a key whose TYPE was changed over data already stored for it reads as.
 *
 * Changing a key's type rewrites the key's declaration and leaves the stored data where it is -
 * nothing migrates it, because a type change preserves nothing. What is left is a stored value of
 * one shape in front of a type that reads another, and nothing checked that before: the type read
 * the value as if it had written it, a relation list reached `strlen((string) $data)` and in dev the
 * object could not be opened at all. The leftovers are read as the key's empty value instead,
 * {@see Classificationstore\Service::canReadStoredValue()}.
 *
 * The switch is made the way an installation arrives at one: the key row is given another type and
 * definition while the value rows stay as they are. That is what the editor does to a key, minus the
 * editor.
 *
 * {@see DocumentSwitchedKeyTypeTest} runs the same tests against a store on the document engine, and
 * adds the cases that only a store holding structured values can have. The two engines do NOT answer
 * alike here, and the reason is the point: an EAV row holds two scalar value columns, so what is read
 * back for a key is whatever those columns hand over - a scalar, or the shape that type's own
 * marshaller rebuilds from them - and no foreign STRUCTURE can arrive. The document engine holds the
 * normalized value itself, so it can and did.
 *
 * @group model.datatype.classificationstore
 */
class SwitchedKeyTypeTest extends AbstractClassificationStoreTest
{
    protected const KEY_PREFIX = 'cs-switched-';

    private const KEYS_TABLE = 'classificationstore_keys';

    private const GROUP_NAME = 'testgroupSwitched';

    /** What a `multiselect` key holds, and what every switched key below has stored for it. */
    private const STORED_LIST = ['a', 'b'];

    /**
     * @var Classificationstore\KeyConfig[]
     */
    private array $createdKeys = [];

    private ?Classificationstore\GroupConfig $createdGroup = null;

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdKeys as $key) {
            Db::get()->delete(self::KEYS_TABLE, ['id' => $key->getId()]);
        }
        $this->createdKeys = [];

        $this->createdGroup?->delete();
        $this->createdGroup = null;

        $this->clearDefinitionCaches();

        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * The reported direction: a list in front of a type whose value is a scalar. The type casts what
     * it is given - `strlen((string) $data)` in {@see ClassDefinition\Data\Extension\Text::isEmpty()},
     * which the read of every key runs to decide whether the fallback language and the inheritance
     * chain are consulted - so the read raised rather than answering.
     */
    public function testAStoredListIsNotReadByAKeyWhoseTypeIsNowAScalar(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('scalar');

        $this->switchKeyTo($keyId, new Input());

        $value = $this->readKey($objectId, $groupId, $keyId);

        if ($this->getStoreEngine() === null) {
            // the EAV marshaller packed the list into one value column, so what an `input` reads
            // back is that column: a scalar, which is a value it can read
            $this->assertSame(implode(',', self::STORED_LIST), $value);

            return;
        }

        $this->assertNull($value, 'the list the old type stored is not interpreted as an input');
    }

    /**
     * And the key answers the same way twice, because nothing about the read rewrites the stored
     * value: reading it marks nothing dirty, so the document the next ordinary save compares against
     * is the one the old type wrote.
     */
    public function testReadingASwitchedKeyLeavesTheStoredValueWhereItIs(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('stable');

        $this->switchKeyTo($keyId, new Input());

        $first = $this->readKey($objectId, $groupId, $keyId);
        $second = $this->readKey($objectId, $groupId, $keyId);

        $this->assertSame($first, $second);
    }

    /**
     * Saving the object again raises nothing either. The save reads the OLD value to compare it with
     * the new one - {@see Classificationstore::setLocalizedKeyValue()} asks the type in force for its
     * resource form - so a leftover reaching that comparison raised there as well, before anything
     * was written: `implode()` on the entries of a list that holds lists
     * ({@see Multiselect::getDataForResource()}).
     */
    public function testSavingAnObjectHoldingASwitchedKeyRaisesNothing(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('resaved');

        $this->switchKeyTo($keyId, new Input());

        $object = $this->reload($objectId);
        $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'written by the type in force');
        $object->save();

        $this->assertSame(
            'written by the type in force',
            $this->readKey($objectId, $groupId, $keyId),
            'the value the type in force writes is the one the key answers with afterwards'
        );
    }

    /**
     * A key that was NOT switched keeps answering with what it holds, so that the check refuses the
     * leftovers rather than the values the store is full of.
     */
    public function testAKeyThatWasNotSwitchedStillAnswersWithItsValue(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('untouched');

        $this->assertSame(self::STORED_LIST, $this->readKey($objectId, $groupId, $keyId));
    }

    /**
     * A group of its own holding one `multiselect` key, with the list stored for it on an object.
     *
     * `multiselect` because it is the one type holding a LIST that both engines can hold: an EAV row
     * takes it through its marshaller and a document takes it as it is.
     *
     * @return array{int, int, int} the group id, the key id and the object id
     */
    protected function storeAList(string $suffix): array
    {
        $group = $this->group();
        $keyId = $this->createMultiselectKey($suffix);

        $object = $this->createObject($suffix);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyId, self::STORED_LIST);
        $object->save();

        return [(int) $group->getId(), $keyId, (int) $object->getId()];
    }

    /**
     * Gives the stored key row another type and definition, which is what changing a key's type in
     * the editor leaves behind: the declaration is rewritten and the value rows are not touched.
     */
    protected function switchKeyTo(int $keyId, ClassDefinition\Data $definition): void
    {
        $definition->setName('keyDefinition');

        Db::get()->update(
            self::KEYS_TABLE,
            ['type' => $definition->getFieldType(), 'definition' => (string) json_encode($definition)],
            ['id' => $keyId]
        );

        $this->clearDefinitionCaches();
    }

    /**
     * The value of one key, read off the object as it comes back from storage.
     */
    protected function readKey(int $objectId, int $groupId, int $keyId): mixed
    {
        return $this->reload($objectId)->getCsstore()->getLocalizedKeyValue($groupId, $keyId);
    }

    protected function reload(int $objectId): Csstore
    {
        $object = Csstore::getById($objectId, ['force' => true]);
        $this->assertInstanceOf(Csstore::class, $object);

        return $object;
    }

    protected function group(): Classificationstore\GroupConfig
    {
        if ($this->createdGroup === null) {
            $group = new Classificationstore\GroupConfig();
            $group->setStoreId($this->store()->getId());
            $group->setName(self::GROUP_NAME);
            $group->save();

            $this->createdGroup = $group;
        }

        return $this->createdGroup;
    }

    protected function createKey(string $suffix, ClassDefinition\Data $definition): int
    {
        $definition->setName('keyDefinition');

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($this->store()->getId());
        $keyConfig->setName(self::KEY_PREFIX . $suffix);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition((string) json_encode($definition));
        $keyConfig->save();

        $this->createdKeys[] = $keyConfig;

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($this->group()->getId());
        $relation->setSorter(1);
        $relation->save();

        return (int) $keyConfig->getId();
    }

    protected function clearDefinitionCaches(): void
    {
        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();
        RuntimeCache::clear();
        Cache::clearAll();
    }

    private function createMultiselectKey(string $suffix): int
    {
        return $this->createKey($suffix, new Multiselect());
    }

    private function createObject(string $suffix): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::KEY_PREFIX . $suffix);
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    private function store(): Classificationstore\StoreConfig
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->assertNotNull($store);

        return $store;
    }
}
