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
 * The relation cases add the other axis a relation brings: the number of targets a key holds. No
 * target is read until the key is read, and reading a key resolves its descriptors with one bulk
 * lookup per element type however many of them there are.
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

        $smallCount = $this->measureRelationLoad($small->getId());
        $smallLookups = $this->countDescriptorLookupStatements();

        $largeCount = $this->measureRelationLoad($large->getId());

        $this->assertGreaterThan(0, $smallCount, 'precondition: loading the store reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'loading 10 relation keys with 10 targets each must cost what one key with one target costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
        $this->assertSame(0, $smallLookups, 'no target may be read before the key is');
        $this->assertSame(0, $this->countDescriptorLookupStatements(), 'no target may be read before the key is');
    }

    public function testReadingOneKeyResolvesItsTargetsWithOneBulkLookupWhateverTheirNumber(): void
    {
        $few = $this->createObjectWithRelations('relation-read-few', 1, 1);
        $many = $this->createObjectWithRelations('relation-read-many', 1, 20);

        $fewLookups = $this->measureRelationRead($few);
        $manyLookups = $this->measureRelationRead($many);

        $this->assertSame(1, $fewLookups, 'one target is resolved by one bulk lookup');
        $this->assertSame(
            $fewLookups,
            $manyLookups,
            'resolving 20 targets must cost the lookups one target costs'
            . ', measured ' . $fewLookups . ' against ' . $manyLookups
        );
    }

    public function testSavingAnObjectWhoseRelationKeysWereNeverReadWritesNothingAndReadsNoTarget(): void
    {
        $object = $this->createObjectWithRelations('relation-save-untouched', 2, 3);

        RuntimeCache::clear();
        $this->startMeasurement();

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertSame(0, $this->countWriteStatements(), 'an unchanged document must not be rewritten');
        $this->assertSame(0, $this->countDescriptorLookupStatements(), 'no target may be resolved by the save');
    }

    public function testEditModeDataDoesNotScaleWithRelationTargets(): void
    {
        $few = $this->createLocalizedObjectWithRelations('relation-editmode-few', 2);
        $many = $this->createLocalizedObjectWithRelations('relation-editmode-many', 20);

        $fewCount = $this->measureRelationEditModeData($few);
        $fewLookups = $this->countDescriptorLookupStatements();

        $manyCount = $this->measureRelationEditModeData($many);

        $this->assertGreaterThan(0, $fewCount, 'precondition: building the editmode data reads the database');
        $this->assertSame(
            $fewCount,
            $manyCount,
            'the editmode data of 20 relation targets must cost what two of them cost'
            . ', measured ' . $fewCount . ' against ' . $manyCount . ' statements'
        );
        $this->assertSame($fewLookups, $this->countDescriptorLookupStatements());
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
     * @return int the statements against a classification store table that loading the store cost
     */
    private function measureRelationLoad(int $objectId): int
    {
        RuntimeCache::clear();
        $this->startMeasurement();

        $object = Csstore::getById($objectId, ['force' => true]);
        $object->getCsstore()->getItems();

        return $this->countReadStatements();
    }

    /**
     * @return int the bulk descriptor lookups reading one key cost
     */
    private function measureRelationRead(Csstore $object): int
    {
        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        [$groupId, $keyId] = $this->firstRelationCoordinates($reloaded->getCsstore());

        $this->startMeasurement();

        $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId);

        return $this->countDescriptorLookupStatements();
    }

    private function measureRelationEditModeData(Csstorelocalized $object): int
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        RuntimeCache::clear();
        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->startMeasurement();

        $fieldDefinition->getDataForEditmode($reloaded->getCsstore(), $reloaded);

        return $this->countReadStatements();
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
