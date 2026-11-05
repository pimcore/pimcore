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

use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * GeneralTest against a store on the document engine: the values of every key type, their languages and their fallbacks
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentGeneralTest extends GeneralTest
{
    use RelationFixtureTrait;

    private const KEY_PREFIX = 'cs-relation-';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * The store is shared across the suite and testClassificationStoreQuantityValue adds a key to it;
     * left there, it would change the key count GeneralTest::testBasics expects of the store.
     */
    public function tearDown(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $group = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $key = $store ? Classificationstore\KeyConfig::getByName('qValue', $store->getId()) : null;

        $key?->delete();
        $group?->delete();

        $this->deleteRelationFixture();

        parent::tearDown();
    }

    public function testAManyToOneRelationKeyRoundTrips(): void
    {
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX . 'one');

        $read = $this->roundTrip('csManyToOne', $target);

        $this->assertInstanceOf(ElementInterface::class, $read);
        $this->assertSame($target->getId(), $read->getId());
    }

    public function testAManyToManyRelationKeyRoundTripsInOrder(): void
    {
        $targets = $this->createTargets(3);

        $read = $this->roundTrip('csManyToMany', $targets);

        $this->assertSame($this->idsOf($targets), $this->idsOf($read));
    }

    public function testAManyToManyObjectRelationKeyRoundTripsInOrder(): void
    {
        $targets = $this->createTargets(3);

        $read = $this->roundTrip('csManyToManyObject', $targets);

        $this->assertSame($this->idsOf($targets), $this->idsOf($read));
    }

    public function testAnAdvancedManyToManyRelationKeyRoundTripsWithItsColumnValues(): void
    {
        $targets = $this->createTargets(2);
        $items = [];

        foreach ($targets as $index => $target) {
            $item = new ElementMetadata('csAdvancedManyToMany', [self::RELATION_COLUMN], $target);
            $item->setData([self::RELATION_COLUMN => 'value-' . $index]);
            $items[] = $item;
        }

        $read = $this->roundTrip('csAdvancedManyToMany', $items);

        $this->assertCount(2, $read);
        $this->assertSame($this->idsOf($targets), $this->idsOf(array_map(
            static fn (ElementMetadata $item): ElementInterface => $item->getElement(),
            $read
        )));

        foreach ($read as $index => $item) {
            $this->assertSame('value-' . $index, $item->getData()[self::RELATION_COLUMN]);
        }
    }

    public function testAnAdvancedManyToManyObjectRelationKeyRoundTripsWithItsColumnValues(): void
    {
        $targets = $this->createTargets(2);
        $items = [];

        foreach ($targets as $index => $target) {
            $item = new ObjectMetadata('csAdvancedManyToManyObject', [self::RELATION_COLUMN], $target);
            $item->setData([self::RELATION_COLUMN => 'value-' . $index]);
            $items[] = $item;
        }

        $read = $this->roundTrip('csAdvancedManyToManyObject', $items);

        $this->assertCount(2, $read);
        $this->assertSame($this->idsOf($targets), $this->idsOf(array_map(
            static fn (ObjectMetadata $item): ElementInterface => $item->getElement(),
            $read
        )));

        foreach ($read as $index => $item) {
            $this->assertSame('value-' . $index, $item->getData()[self::RELATION_COLUMN]);
        }
    }

    /**
     * Stores one value and reads it back from a freshly loaded object, which is the round trip
     * through the stored document.
     */
    private function roundTrip(string $keyName, mixed $value): mixed
    {
        $group = $this->createRelationFixture();
        $object = $this->createCsObject();

        $object->getCsstore()->setLocalizedKeyValue(
            (int) $group->getId(),
            $this->getRelationKeyId($keyName),
            $value
        );
        $object->save();

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        return $reloaded->getCsstore()->getLocalizedKeyValue(
            (int) $group->getId(),
            $this->getRelationKeyId($keyName)
        );
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

    /**
     * @param ElementInterface[] $elements
     *
     * @return int[]
     */
    private function idsOf(array $elements): array
    {
        return array_map(static fn (ElementInterface $element): int => $element->getId(), $elements);
    }
}
