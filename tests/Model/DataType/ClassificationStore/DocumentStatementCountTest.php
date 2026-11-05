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
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The load counts against a store on the document engine: one document per group is read in one
 * statement, so none of the read paths scales with groups, keys or languages either.
 *
 * The relation cases add the other axis a relation brings: the number of targets a key holds. Every
 * count here is the connection's total - the element loads included, not only the statements the
 * store itself issues - because that is the only count in which a bound means anything.
 *
 * Two different bounds come out of that, and they are asserted as what they are:
 *
 * - Loading a store, and saving one whose relation keys were never read, does not scale with the
 *   number of keys or targets at all. Nothing is resolved, so the totals are equal.
 * - Reading a key, and building the edit mode payload, costs one element load per target and not a
 *   statement more. Pimcore instantiates elements one at a time -
 *   {@see \Pimcore\Model\DataObject\AbstractObject::getById()} has no bulk form and a plain
 *   relation field of a class pays the same per target,
 *   {@see \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation::loadData()} - so
 *   the growth is measured against one measured element load rather than asserted away.
 *
 * @group model.datatype.classificationstore
 */
class DocumentStatementCountTest extends StatementCountTest
{
    private const FIELDNAME = 'csstore';

    private const KEY_PREFIX = 'cs-count-relation-';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    public function testLoadingDoesNotScaleWithRelationKeysOrTargets(): void
    {
        $small = $this->createObjectWithRelations('relation-load-small', 1, 1);
        $large = $this->createObjectWithRelations('relation-load-large', 10, 10);
        $largeTargets = $this->storedTargetIds($large);

        $smallCount = $this->measureRelationLoad($small->getId());
        $largeCount = $this->measureRelationLoad($large->getId());

        $this->assertGreaterThan(0, $smallCount, 'precondition: loading the store reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'loading 10 relation keys with 10 targets each must cost what one key with one target costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
        $this->assertNoElementInstantiated($largeTargets, 'no target may be read before the key is');
    }

    /**
     * The one bound that can be stated absolutely for a read is that the store adds nothing per
     * target: 20 targets cost what one target costs plus 19 element loads, and the element load is
     * measured in the same run rather than assumed.
     */
    public function testReadingOneKeyCostsOneElementLoadPerTargetAndNotAStatementMore(): void
    {
        $few = $this->createObjectWithRelations('relation-read-few', 1, 1);
        $many = $this->createObjectWithRelations('relation-read-many', 1, 20);

        $manyTargets = $this->storedTargetIds($many);
        $elementLoad = $this->measureElementLoad($manyTargets[0], $manyTargets[1]);

        $fewCount = $this->measureRelationRead($few);
        $manyCount = $this->measureRelationRead($many);

        $this->assertGreaterThan(0, $elementLoad, 'precondition: loading an element reads the database');
        $this->assertSame(
            19 * $elementLoad,
            $manyCount - $fewCount,
            'reading 20 targets instead of one must cost 19 element loads and nothing else'
            . ', measured ' . $fewCount . ' against ' . $manyCount . ' statements'
            . ' at ' . $elementLoad . ' per element'
        );
    }

    /**
     * Reading one key of a store that holds several must leave the other keys as the descriptors
     * they are: the targets of the keys that were not read are never instantiated.
     */
    public function testReadingOneKeyReadsNoTargetOfTheOtherKeys(): void
    {
        $group = $this->createGroup('relationgroup');
        $read = $this->createRelationKey('relationkeyread');
        $untouched = $this->createRelationKey('relationkeyuntouched');
        $this->assignKey($group, $read, 0);
        $this->assignKey($group, $untouched, 1);

        $readTargets = $this->createTargets(2);
        $untouchedTargets = $this->createTargets(2);

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('relation-read-one-of-many');
        $object->setPublished(true);

        $store = $object->getCsstore();
        $store->setLocalizedKeyValue((int) $group->getId(), (int) $read->getId(), $readTargets);
        $store->setLocalizedKeyValue((int) $group->getId(), (int) $untouched->getId(), $untouchedTargets);
        $store->setActiveGroups([$group->getId() => true]);
        $object->save();

        RuntimeCache::clear();
        $resolved = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue((int) $group->getId(), (int) $read->getId());

        $this->assertSame(
            $this->idsOf($readTargets),
            $this->idsOf($resolved),
            'precondition: the key that was read answers with its own targets'
        );
        $this->assertNoElementInstantiated(
            $this->idsOf($untouchedTargets),
            'a key that was not read must not instantiate a target'
        );
    }

    public function testSavingAnObjectWhoseRelationKeysWereNeverReadWritesNothingAndReadsNoTarget(): void
    {
        $object = $this->createObjectWithRelations('relation-save-untouched', 2, 3);
        $targets = $this->storedTargetIds($object);

        RuntimeCache::clear();
        $this->startMeasurement();

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertSame(0, $this->countWriteStatements(), 'an unchanged document must not be rewritten');
        $this->assertNoElementInstantiated($targets, 'no target may be resolved by the save');
    }

    /**
     * A save that never read its relation keys writes the descriptors back as they are, so the rows
     * of the store it touches do not grow with the number of targets they hold either.
     *
     * The classification store's own statements are what is counted here, paired with the assertion
     * that no target was instantiated - which is what a total would otherwise be hiding.
     */
    public function testSavingAnObjectWhoseRelationKeysWereNeverReadDoesNotTouchTheStorePerTarget(): void
    {
        $few = $this->createObjectWithRelations('relation-save-few', 2, 1);
        $many = $this->createObjectWithRelations('relation-save-many', 2, 20);
        $manyTargets = $this->storedTargetIds($many);

        $fewCount = $this->measureUntouchedSave($few->getId());
        $manyCount = $this->measureUntouchedSave($many->getId());

        $this->assertGreaterThan(0, $fewCount, 'precondition: loading and saving reads the store');
        $this->assertSame(
            $fewCount,
            $manyCount,
            'saving an unread store of 20 targets must touch the rows one of one target touches'
            . ', measured ' . $fewCount . ' against ' . $manyCount . ' statements'
        );
        $this->assertNoElementInstantiated($manyTargets, 'and must resolve none of them');
    }

    /**
     * The payload pays one element load per target it renders and no statement of the store's own
     * per target: 20 targets cost what two cost plus 18 element loads.
     */
    public function testEditModeDataCostsOneElementLoadPerTargetAndNotAStatementMore(): void
    {
        $few = $this->createLocalizedObjectWithRelations('relation-editmode-few', 2);
        $many = $this->createLocalizedObjectWithRelations('relation-editmode-many', 20);

        $fewTargets = $this->storedTargetIds($few);
        $elementLoad = $this->measureElementLoad($fewTargets[0], $fewTargets[1]);

        $fewCount = $this->measureRelationEditModeData($few);
        $manyCount = $this->measureRelationEditModeData($many);

        $this->assertGreaterThan(0, $fewCount, 'precondition: building the editmode data reads the database');
        $this->assertSame(
            18 * $elementLoad,
            $manyCount - $fewCount,
            'the editmode data of 20 targets instead of two must cost 18 element loads and nothing else'
            . ', measured ' . $fewCount . ' against ' . $manyCount . ' statements'
            . ' at ' . $elementLoad . ' per element'
        );
    }

    /**
     * The payload keeps the levels it has today - every active group and every valid language, not
     * only the group being rendered - with the relation value resolved into its elements.
     */
    public function testTheEditModeDataKeepsItsShapeForARelationKey(): void
    {
        $object = $this->createLocalizedObjectWithRelations('relation-editmode-shape', 2);
        $store = $object->getCsstore();
        [$groupId, $keyId] = $this->firstRelationCoordinates($store);

        $payload = $object->getClass()->getFieldDefinition(self::FIELDNAME)->getDataForEditmode($store, $object);

        $this->assertSame(
            ['data', 'metaData', 'inherited', 'activeGroups', 'groupCollectionMapping'],
            array_keys($payload)
        );
        $this->assertArrayHasKey($groupId, $payload['data']['default']);
        $this->assertArrayHasKey($keyId, $payload['data']['default'][$groupId]);
        $this->assertArrayHasKey($groupId, $payload['activeGroups']);
    }

    /**
     * @return int every statement loading the object and its store cost
     */
    private function measureRelationLoad(int $objectId): int
    {
        RuntimeCache::clear();
        $this->startMeasurement();

        $object = Csstore::getById($objectId, ['force' => true]);
        $object->getCsstore()->getItems();

        return $this->countStatements();
    }

    /**
     * @return int every statement loading the object and then reading one of its keys cost
     */
    private function measureRelationRead(Csstore $object): int
    {
        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        [$groupId, $keyId] = $this->firstRelationCoordinates($reloaded->getCsstore());

        $this->startMeasurement();

        $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId);

        return $this->countStatements();
    }

    /**
     * @return int the statements against a classification store table that loading the object and
     *             saving it again cost, with none of its relation keys read in between
     */
    private function measureUntouchedSave(int $objectId): int
    {
        RuntimeCache::clear();
        $this->startMeasurement();

        Csstore::getById($objectId, ['force' => true])->save();

        return $this->countReadStatements() + $this->countWriteStatements();
    }

    /**
     * @return int every statement building the edit mode payload cost
     */
    private function measureRelationEditModeData(Csstorelocalized $object): int
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        RuntimeCache::clear();
        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->startMeasurement();

        $fieldDefinition->getDataForEditmode($reloaded->getCsstore(), $reloaded);

        return $this->countStatements();
    }

    /**
     * The ids the store's relation keys hold, read off the stored descriptors rather than off the
     * resolved elements, so that asking the question instantiates nothing.
     *
     * @return int[]
     */
    private function storedTargetIds(Csstore|Csstorelocalized $object): array
    {
        $ids = [];

        foreach ($this->readDocuments($object) as $keys) {
            foreach ($keys as $entries) {
                foreach ($entries as $entry) {
                    foreach ($entry['d'] ?? [] as $descriptor) {
                        $ids[] = (int) $descriptor['id'];
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array<int, array<array<string, mixed>>>> groupId => keyId => entries
     */
    private function readDocuments(Csstore|Csstorelocalized $object): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT `groupId`, `values` FROM '
            . Db::get()->quoteIdentifier('object_classificationstore_doc_' . $object->getClassId())
            . ' WHERE `id` = ? AND `fieldname` = ?',
            [$object->getId(), self::FIELDNAME]
        );

        $documents = [];

        foreach ($rows as $row) {
            $document = json_decode((string) $row['values'], true);
            $documents[(int) $row['groupId']] = $document['k'] ?? [];
        }

        return $documents;
    }

    /**
     * @return array{0: int, 1: int} the group and key of the first stored value
     */
    private function firstRelationCoordinates(Classificationstore $store): array
    {
        $items = $store->getItems();
        $groupId = (int) array_key_first($items);

        return [$groupId, (int) array_key_first($items[$groupId])];
    }

    private function createObjectWithRelations(string $key, int $keyCount, int $targetCount): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);

        $this->fillWithRelations($object->getCsstore(), $keyCount, $targetCount);
        $object->save();

        return $object;
    }

    private function createLocalizedObjectWithRelations(string $key, int $targetCount): Csstorelocalized
    {
        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);

        $this->fillWithRelations($object->getCsstore(), 1, $targetCount);
        $object->save();

        return $object;
    }

    private function fillWithRelations(Classificationstore $store, int $keyCount, int $targetCount): void
    {
        $group = $this->createGroup('relationgroup');
        $targets = $this->createTargets($targetCount);
        $activeGroups = [$group->getId() => true];

        for ($index = 0; $index < $keyCount; $index++) {
            $keyConfig = $this->createRelationKey('relationkey' . $index);
            $this->assignKey($group, $keyConfig, $index);

            $store->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), $targets);
        }

        $store->setActiveGroups($activeGroups);
    }

    private function createRelationKey(string $label): KeyConfig
    {
        $definition = new ManyToManyObjectRelation();

        return $this->createKey($label, true, $definition);
    }

    /**
     * @param ElementInterface[] $elements
     *
     * @return int[]
     */
    private function idsOf(array $elements): array
    {
        return array_map(static fn (ElementInterface $element): int => $element->getId(), $elements);
    }

    /**
     * @return ElementInterface[]
     */
    private function createTargets(int $count): array
    {
        $targets = [];

        for ($index = 0; $index < $count; $index++) {
            $targets[] = TestHelper::createEmptyObject(self::KEY_PREFIX . $index . '-');
        }

        return $targets;
    }
}
