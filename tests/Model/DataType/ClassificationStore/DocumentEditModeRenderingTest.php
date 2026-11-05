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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The edit mode payload of a relation-typed key is rendered from the stored descriptors, without
 * instantiating a target, so there are two ways to produce it: the one the payload takes and the one
 * the key's own `getDataForEditmode()` takes from the elements. They have to agree, and this asserts
 * it over one fixture per relation type rather than taking the rendering's word for it.
 *
 * Both are run over the same stored value in the same test, because that is what makes a divergence
 * unmergeable rather than reviewable: the renderer duplicates five shapes it does not own, and a
 * shape that drifts is a payload the object editor renders wrongly with every gate green.
 *
 * @group model.datatype.classificationstore
 */
class DocumentEditModeRenderingTest extends AbstractClassificationStoreTest
{
    use RelationFixtureTrait;

    private const FIELDNAME = 'csstore';

    private const KEY_PREFIX = 'cs-editmode-relation-';

    private const DEFAULT_LANGUAGE = 'default';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        Cache::disable();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->deleteRelationFixture();
        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function relationKeyProvider(): array
    {
        $cases = [];

        foreach (array_keys(self::RELATION_TYPES) as $keyName) {
            $cases[$keyName] = [$keyName];
        }

        return $cases;
    }

    /**
     * @dataProvider relationKeyProvider
     */
    public function testTheRenderedPayloadIsWhatTheElementsWouldHaveProduced(string $keyName): void
    {
        $this->createRelationFixture([$keyName]);
        $object = $this->storeValue($keyName, $this->buildValue($keyName, $this->createTargets(2)));

        $this->assertSame(
            $this->fromElements($keyName, $object),
            $this->fromPayload($keyName, $object),
            'the rendered payload of ' . $keyName . ' must equal what its elements would have produced'
        );
    }

    /**
     * A target that is gone must drop out of both, and the surviving rows must renumber the same
     * way - which is what the `rowId` of the advanced pair is built from.
     *
     * Both paths drop a dead descriptor, so this asserts that the payload is right after a deletion
     * rather than which path produced it; what the dropping then does to the stored value is
     * asserted by the case below.
     *
     * @dataProvider relationKeyProvider
     */
    public function testTheRenderedPayloadAgreesAfterATargetIsDeleted(string $keyName): void
    {
        $this->createRelationFixture([$keyName]);
        $targets = $this->createTargets(3);
        $object = $this->storeValue($keyName, $this->buildValue($keyName, $targets));

        $targets[0]->delete();

        $this->assertSame(
            $this->fromElements($keyName, $object),
            $this->fromPayload($keyName, $object),
            'the rendered payload of ' . $keyName . ' must equal what its elements would have'
            . ' produced once a target is gone'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function advancedRelationKeyProvider(): array
    {
        $cases = [];

        foreach (self::ADVANCED_RELATION_TYPES as $keyName) {
            $cases[$keyName] = [$keyName];
        }

        return $cases;
    }

    /**
     * Rendering the payload is what finds a dead descriptor, and taking it out renumbers the
     * survivors. The per-relation column values of an advanced key are addressed by the position of
     * the entry they belong to, in object_classificationstore_meta_<CLASS>, so a save that rewrote
     * the document without rewriting those rows would hand the surviving relation the deleted one's
     * column value - silent corruption with every gate green.
     *
     * @dataProvider advancedRelationKeyProvider
     */
    public function testASurvivingAdvancedRelationKeepsItsOwnColumnValueAfterATargetIsDeleted(
        string $keyName
    ): void {
        $this->createRelationFixture([$keyName]);

        $targets = [
            TestHelper::createEmptyObject(self::KEY_PREFIX . 'advanced-first-'),
            TestHelper::createEmptyObject(self::KEY_PREFIX . 'advanced-second-'),
        ];
        $object = $this->storeValue($keyName, $this->buildValue($keyName, $targets));

        $targets[0]->delete();

        RuntimeCache::clear();
        $edited = Csstore::getById($object->getId(), ['force' => true]);
        $edited->getClass()
            ->getFieldDefinition(self::FIELDNAME)
            ->getDataForEditmode($edited->getCsstore(), $edited);
        $edited->save();

        RuntimeCache::clear();
        $read = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue($this->getRelationGroupId(), $this->getRelationKeyId($keyName));

        $this->assertCount(1, $read, 'the descriptor of the deleted target must be gone');
        $this->assertSame($targets[1]->getId(), $read[0]->getElement()->getId());
        $this->assertSame(
            'value-1',
            $read[0]->getData()[self::RELATION_COLUMN] ?? null,
            'the surviving relation must keep its own column value, not the deleted one'
        );
    }

    /**
     * The two to-many types that accept any element kind must describe an asset and a folder the way
     * their own `getDataForEditmode()` does - a folder is not a `Concrete`, and an asset carries no
     * published flag of its own.
     */
    public function testTheRenderedPayloadAgreesForAnAssetAndAFolderTarget(): void
    {
        $targets = [
            TestHelper::createImageAsset(self::KEY_PREFIX . 'asset-'),
            $this->createObjectFolder(),
            $this->createTargets(1)[0],
        ];

        foreach (['csManyToMany', 'csAdvancedManyToMany'] as $keyName) {
            $this->createRelationFixture([$keyName]);
            $object = $this->storeValue($keyName, $this->buildValue($keyName, $targets));

            $this->assertSame(
                $this->fromElements($keyName, $object),
                $this->fromPayload($keyName, $object),
                'the rendered payload of ' . $keyName . ' must equal what its elements would have'
                . ' produced for an asset and a folder'
            );

            $this->deleteRelationFixture();
        }
    }

    /**
     * A to-one relation describes its target through `Element\Service::isPublished()`, which answers
     * true for an element kind that carries no published flag of its own - an object folder and an
     * asset - instead of reading the column the rendering reads.
     */
    public function testTheRenderedPayloadAgreesForAToOneRelationToAFolderOrAnAsset(): void
    {
        $targets = [
            $this->createObjectFolder(),
            TestHelper::createImageAsset(self::KEY_PREFIX . 'to-one-asset-'),
        ];

        foreach ($targets as $target) {
            $this->createRelationFixture(['csManyToOne']);
            $object = $this->storeValue('csManyToOne', $target);

            $this->assertSame(
                $this->fromElements('csManyToOne', $object),
                $this->fromPayload('csManyToOne', $object),
                'the rendered payload of a to-one relation to a ' . $target->getType()
                . ' must equal what its element would have produced'
            );

            $this->deleteRelationFixture();
        }
    }

    /**
     * The payload the production path produces for the key, read out of the full edit mode data.
     */
    private function fromPayload(string $keyName, Csstore $object): mixed
    {
        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        $payload = $reloaded->getClass()
            ->getFieldDefinition(self::FIELDNAME)
            ->getDataForEditmode($reloaded->getCsstore(), $reloaded);

        return $payload['data'][self::DEFAULT_LANGUAGE][$this->getRelationGroupId()][$this->getRelationKeyId($keyName)]
            ?? null;
    }

    /**
     * The same payload produced the other way round: the stored descriptors denormalized into their
     * elements, and the key's own `getDataForEditmode()` run over those.
     */
    private function fromElements(string $keyName, Csstore $object): mixed
    {
        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        $fieldDefinition = $this->getKeyDefinition($keyName);
        $this->assertInstanceOf(NormalizerInterface::class, $fieldDefinition);

        $elements = $fieldDefinition->denormalize(
            $this->storedValue($keyName, $reloaded),
            ['object' => $reloaded, 'fieldDefinition' => $fieldDefinition]
        );

        return $fieldDefinition->getDataForEditmode($elements, $reloaded);
    }

    /**
     * The normalized value the document holds for the key, taken off the store's items so that the
     * descriptor is read rather than the elements it addresses.
     */
    private function storedValue(string $keyName, Csstore $object): mixed
    {
        $items = $object->getCsstore()->getItems();
        $descriptor = $items[$this->getRelationGroupId()][$this->getRelationKeyId($keyName)][self::DEFAULT_LANGUAGE]
            ?? null;

        $this->assertNotNull($descriptor, 'precondition: the key holds a stored descriptor');

        return $descriptor->getValue();
    }

    private function getKeyDefinition(string $keyName): Data
    {
        return Service::getFieldDefinitionFromKeyConfig(
            DefinitionCache::get($this->getRelationKeyId($keyName))
        );
    }

    private function storeValue(string $keyName, mixed $value): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::KEY_PREFIX . $keyName . '-' . uniqid());
        $object->setPublished(true);

        $object->getCsstore()->setLocalizedKeyValue(
            $this->getRelationGroupId(),
            $this->getRelationKeyId($keyName),
            $value
        );
        $object->save();

        return $object;
    }

    /**
     * The value one key type takes: a single element for the to-one type, a list for the two to-many
     * ones, and a list of metadata items carrying one column value for the advanced pair.
     *
     * @param ElementInterface[] $targets
     */
    private function buildValue(string $keyName, array $targets): mixed
    {
        if ($keyName === 'csManyToOne') {
            return $targets[0];
        }

        if (!in_array($keyName, self::ADVANCED_RELATION_TYPES, true)) {
            return $targets;
        }

        $items = [];

        foreach ($targets as $index => $target) {
            $items[] = $this->buildMetadataItem($keyName, $target, 'value-' . $index);
        }

        return $items;
    }

    private function buildMetadataItem(
        string $keyName,
        ElementInterface $target,
        string $value
    ): ElementMetadata|ObjectMetadata {
        $item = $keyName === 'csAdvancedManyToManyObject' && $target instanceof Concrete
            ? new ObjectMetadata($keyName, [self::RELATION_COLUMN], $target)
            : new ElementMetadata($keyName, [self::RELATION_COLUMN], $target);

        $item->setData([self::RELATION_COLUMN => $value]);

        return $item;
    }

    /**
     * Every second target is left unpublished: `published` is a column the rendering reads off the
     * element table while the key's own `getDataForEditmode()` reads it off the element, so a
     * fixture of published targets alone would never tell the two apart.
     *
     * @return ElementInterface[]
     */
    private function createTargets(int $count): array
    {
        $targets = [];

        for ($index = 0; $index < $count; $index++) {
            $targets[] = TestHelper::createEmptyObject(
                self::KEY_PREFIX . $index . '-',
                true,
                $index % 2 === 0
            );
        }

        return $targets;
    }

    private function createObjectFolder(): Folder
    {
        $folder = new Folder();
        $folder->setParentId(1);
        $folder->setKey(self::KEY_PREFIX . 'folder-' . uniqid());
        $folder->save();

        return $folder;
    }
}
