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

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Consent as ConsentDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopolygon;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery as ImageGalleryDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueKey;
use Pimcore\Model\DataObject\ClassDefinition\Data\StructuredTable as StructuredTableDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug as UrlSlugDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\DataObject\Data\NumericRange;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tests\Support\Helper\DataType\CustomValueObject;
use Pimcore\Tests\Support\Helper\DataType\MixedNormalizingField;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;

/**
 * GeneralTest against a store on the document engine: the values of every key type, their languages and their fallbacks
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentGeneralTest extends GeneralTest
{
    use RelationFixtureTrait;

    private const IMPERIAL = 'imperial';

    private const METRIC = 'metric';

    private const MULTI_VALUE_KEY = 'mvHeight';

    private const MULTI_VALUE_GROUP = 'testgroupMultiValue';

    private const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    private const STORE_NAME = 'teststore';

    private const FIELDNAME = 'csstore';

    private const KEY_PREFIX = 'cs-structured-';

    private const CLASS_NAME = 'csstore';

    private const DOCUMENT_TABLE = 'object_classificationstore_doc_';

    private const ENCRYPTED_GROUP_NAME = 'testgroupEncryptedGeopoint';

    private const ENCRYPTED_KEY_NAME = 'encryptedGeopoint';

    private const ENCRYPTED_CONSENT_KEY_NAME = 'encryptedConsent';

    private const MIXED_GROUP_NAME = 'testgroupEncryptedMixed';

    private const MIXED_KEY_NAME = 'encryptedMixedNormalizing';

    private const NO_RESOURCE_FORM_GROUP_NAME = 'testgroupNoResourceForm';

    private const SERIALIZATION_GROUP_NAME = 'testgroupEncryptedSerialization';

    private const EXCLUDED_DELEGATE_GROUP_NAME = 'testgroupEncryptedExcludedDelegate';

    private const ENCRYPTED_SLUG_KEY_NAME = 'encryptedUrlSlug';

    private const ENCRYPTED_CALCULATED_KEY_NAME = 'encryptedCalculatedValue';

    private const ENCRYPTED_POLYGON_KEY_NAME = 'encryptedGeopolygon';

    private const ENCRYPTED_GALLERY_KEY_NAME = 'encryptedImageGallery';

    private const PLAIN_MIXED_KEY_NAME = 'mixedNormalizing';

    private const DEFAULT_LANGUAGE = 'default';

    private const RELATION_KEY_PREFIX = 'cs-relation-';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * The store is shared across the suite and testClassificationStoreQuantityValue adds a key to it;
     * left there, it would change the key count GeneralTest::testBasics expects of the store.
     * The qualified-value fixture of this class is its own for the same reason.
     */
    public function tearDown(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $group = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $key = $store ? Classificationstore\KeyConfig::getByName('qValue', $store->getId()) : null;

        $key?->delete();
        $group?->delete();

        $this->deleteRelationFixture();

        if ($store) {
            Classificationstore\KeyConfig::getByName(self::MULTI_VALUE_KEY, $store->getId())?->delete();
            Classificationstore\GroupConfig::getByName(self::MULTI_VALUE_GROUP, $store->getId())?->delete();
        }

        $this->clearDefinitionCaches();

        parent::tearDown();
    }

    public function testAManyToOneRelationKeyRoundTrips(): void
    {
        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'one');

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
            $targets[] = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . $index . '-');
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

    /**
     * Every type the document engine unlocks, defined as a key, filled, saved, reloaded and
     * compared: the value that comes back has to normalize to what the value that went in
     * normalizes to.
     *
     * The comparison is on the normalized form rather than on the value objects, because a loaded
     * value carries the owner metadata the storage gave it while the instance that was handed in
     * carries the one the setter gave it - a difference about neither storage nor type. Everything
     * the type puts into a value column, or in this case into the document, is in the normalized
     * form.
     */
    public function testEveryUnlockedStructuredTypeRoundTripsOnADocumentStore(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $values = $this->structuredValues();

        $this->assertCount(
            count(self::STRUCTURED_KEY_NAMES),
            $values,
            'every structured key the fixture defines is written'
        );

        try {
            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'round-trip');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);

            foreach ($values as $keyName => $value) {
                $csField->setLocalizedKeyValue($group->getId(), $this->keyId($store, $keyName), $value);
            }

            $object->save();

            // after the save, so that both sides carry the owner metadata the setter assigned
            $expected = [];
            foreach ($values as $keyName => $value) {
                $expected[$keyName] = $this->normalize($store, $keyName, $value, $object);
            }

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);
            $reloadedField = $reloaded->getCsstore();

            foreach (array_keys($values) as $keyName) {
                $loaded = $reloadedField->getLocalizedKeyValue($group->getId(), $this->keyId($store, $keyName));

                $this->assertNotNull($loaded, $keyName . ' came back empty');
                $this->assertEquals(
                    $expected[$keyName],
                    $this->normalize($store, $keyName, $loaded, $reloaded),
                    $keyName . ' does not come back as what was stored'
                );
            }
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    /**
     * The other half of the encryptedField rule: a delegate with no classification store marshaller
     * is refused on an EAV store, and on a document store the key is definable and its value comes
     * back - the secret still encrypted in the document, the coordinates still readable through the
     * model.
     */
    public function testAnEncryptedFieldDelegatingToAStructuredTypeRoundTripsOnADocumentStore(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::ENCRYPTED_GROUP_NAME);
        $group->save();

        try {
            $keyConfig = $this->createEncryptedGeopointKey($store, $group);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'encrypted-geopoint');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue(
                $group->getId(),
                (int) $keyConfig->getId(),
                new EncryptedField(new Geopoint(), new GeoCoordinates(48.2, 16.37))
            );
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $loaded = $reloaded->getCsstore()
                ->getLocalizedKeyValue($group->getId(), (int) $keyConfig->getId());

            $this->assertInstanceOf(EncryptedField::class, $loaded);
            $coordinates = $loaded->getPlain();
            $this->assertInstanceOf(GeoCoordinates::class, $coordinates);
            $this->assertSame(48.2, $coordinates->getLatitude());
            $this->assertSame(16.37, $coordinates->getLongitude());

            // the document holds the ciphertext, not the coordinates
            $stored = (string) Db::get()->fetchOne(
                'SELECT `values` FROM ' . self::DOCUMENT_TABLE . ClassDefinition::getByName(self::CLASS_NAME)->getId()
                . ' WHERE id = ? AND fieldname = ? AND groupId = ?',
                [$object->getId(), self::FIELDNAME, $group->getId()]
            );
            $this->assertStringNotContainsString('48.2', $stored, 'the coordinates must not be in cleartext');
        } finally {
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\GroupConfig::getByName(self::ENCRYPTED_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * A delegate whose declared normalized type and whose value disagree.
     *
     * `mixedNormalizingObject` declares `mixed` - which the holdability check reads as
     * scalar-compatible, correctly, because a key definition has nothing but the declaration - and
     * returns an array. The encryptedField marshaller has nothing to pack such a value into, so
     * whether it needs the JSON envelope is decided from the value in hand and the registered
     * marshallers; deciding it from the declaration sends this value into the marshaller's array
     * branch and stores two nulls.
     */
    public function testAnEncryptedFieldDelegatingToAMixedNormalizingTypeRoundTripsOnADocumentStore(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::MIXED_GROUP_NAME);
        $group->save();

        try {
            $keyConfig = $this->createMixedNormalizingKey($store, $group);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'encrypted-mixed');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue(
                $group->getId(),
                (int) $keyConfig->getId(),
                new EncryptedField(new MixedNormalizingField(), new CustomValueObject('a structured secret'))
            );
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $loaded = $reloaded->getCsstore()
                ->getLocalizedKeyValue($group->getId(), (int) $keyConfig->getId());

            $this->assertInstanceOf(EncryptedField::class, $loaded);
            $plain = $loaded->getPlain();
            $this->assertInstanceOf(CustomValueObject::class, $plain);
            $this->assertSame('a structured secret', $plain->getText());

            $stored = (string) Db::get()->fetchOne(
                'SELECT `values` FROM ' . self::DOCUMENT_TABLE . ClassDefinition::getByName(self::CLASS_NAME)->getId()
                . ' WHERE id = ? AND fieldname = ? AND groupId = ?',
                [$object->getId(), self::FIELDNAME, $group->getId()]
            );
            $this->assertStringNotContainsString(
                'a structured secret',
                $stored,
                'the value must not be in cleartext'
            );
        } finally {
            Classificationstore\KeyConfig::getByName(self::MIXED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\GroupConfig::getByName(self::MIXED_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * A scalar that happens to look like the engine's JSON envelope is stored as the scalar it is,
     * read back as that scalar, and is never confused with a real envelope carrying the same
     * members.
     *
     * The engine wraps a non-scalar `encryptedField` value in `{"_csJson":...}` so the encryption
     * has a scalar to encrypt. The string `{"_csJson":[1,2]}` is a perfectly good value of such a
     * key, is a scalar and is therefore stored unwrapped - and the ciphertext of the two is
     * identical, because the envelope of `[1,2]` is that same string. So nothing inside the
     * encrypted bytes can say which shape is stored, which is why the entry carries the answer
     * beside them. Two things went wrong without it:
     *
     * - the read unwrapped whatever looked like an envelope, so a previously readable string came
     *   back as the array `[1,2]`;
     * - `encodesTheSameValue()` compares the two plaintexts to keep a stored ciphertext when the
     *   value has not changed, and these two plaintexts are equal - so switching the value between
     *   the two shapes kept the stored row and answered a read of one with the other.
     *
     * Both directions are asserted, because each keeps the other honest: writing the qualifier but
     * leaving it out of the comparison fails the second half, and comparing it while still guessing
     * the shape on read fails the first.
     */
    public function testAnEnvelopeShapedScalarIsNeitherUnwrappedNorConfusedWithAnEnvelope(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::MIXED_GROUP_NAME);
        $group->save();

        $envelopeShapedScalar = '{"_csJson":[1,2]}';

        try {
            $keyConfig = $this->createMixedNormalizingKey($store, $group);
            $keyId = (int) $keyConfig->getId();

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'envelope-shaped-scalar');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue(
                $group->getId(),
                $keyId,
                new EncryptedField(new MixedNormalizingField(), $envelopeShapedScalar)
            );
            $object->save();

            $this->assertSame(
                $envelopeShapedScalar,
                $this->readPlainValue($object, $group, $keyId),
                'a scalar that looks like an envelope comes back as the scalar it is'
            );

            // and the entry says nothing about an envelope, which is what every entry written
            // before the qualifier existed looks like: such an entry is read as it stands
            $this->assertFalse(
                $this->entryClaimsAnEnvelope($object, $group, $keyId),
                'a value that was not wrapped carries no envelope qualifier'
            );

            // the same key, now holding the array whose envelope is byte for byte the string above
            $this->setPlainValue($object, $group, $keyId, [1, 2]);

            $this->assertSame(
                [1, 2],
                $this->readPlainValue($object, $group, $keyId),
                'the array is not answered with the stored string of equal plaintext'
            );
            $this->assertTrue(
                $this->entryClaimsAnEnvelope($object, $group, $keyId),
                'the wrapped value says so in its entry'
            );

            // and back again
            $this->setPlainValue($object, $group, $keyId, $envelopeShapedScalar);

            $this->assertSame(
                $envelopeShapedScalar,
                $this->readPlainValue($object, $group, $keyId),
                'the scalar is not answered with the stored envelope of equal plaintext'
            );
            $this->assertFalse(
                $this->entryClaimsAnEnvelope($object, $group, $keyId),
                'the unwrapped value says so in its entry'
            );
        } finally {
            Classificationstore\KeyConfig::getByName(self::MIXED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\GroupConfig::getByName(self::MIXED_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * Replacing the value of a key reaches the store's dirty flag even when the key's field
     * definition cannot produce a resource form of either value.
     *
     * Whether the store is dirty is decided by comparing what the field definition's
     * `getDataForResource()` makes of the value that was there against what it makes of the one
     * being set. Two kinds of key this release accepts answer `null` to that for every value they
     * are handed: a type that is not `ResourcePersistenceAwareInterface` at all - the document
     * engine stores what `normalize()` returns and asks for no resource form - and an
     * `encryptedField` whose delegate is not one, because
     * {@see EncryptedFieldDefinition::getDataForResource()} returns `null` unless the delegate can
     * produce it. Both compared equal to whatever was stored before, so the engine found a clean
     * store and wrote nothing: the edit was lost without an error.
     *
     * Nothing but the set is done here - no `setActiveGroups()`, no setter on the value itself -
     * because each of those marks the store dirty on its own and would hide it.
     */
    public function testReplacingAValueWithNoResourceFormIsSaved(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::NO_RESOURCE_FORM_GROUP_NAME);
        $group->save();

        try {
            $plainKeyId = (int) $this->createPlainMixedNormalizingKey($store, $group)->getId();
            $encryptedKeyId = (int) $this->createMixedNormalizingKey($store, $group)->getId();

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'no-resource-form');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $plainKeyId, new CustomValueObject('first'));
            $csField->setLocalizedKeyValue(
                $group->getId(),
                $encryptedKeyId,
                new EncryptedField(new MixedNormalizingField(), new CustomValueObject('first secret'))
            );
            $object->save();

            // a type with no resource form at all
            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $reloaded->getCsstore()->setLocalizedKeyValue(
                $group->getId(),
                $plainKeyId,
                new CustomValueObject('second')
            );
            $this->assertTrue(
                $reloaded->getCsstore()->hasDirtyFields(),
                'replacing the value of a key whose type has no resource form marks the store dirty'
            );
            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);

            $plainValue = $again->getCsstore()->getLocalizedKeyValue($group->getId(), $plainKeyId);
            $this->assertInstanceOf(CustomValueObject::class, $plainValue);
            $this->assertSame('second', $plainValue->getText(), 'the replaced value is the one stored');

            // and an encryptedField whose delegate has none either, which is what makes its own
            // getDataForResource() answer null for every value
            $again->getCsstore()->setLocalizedKeyValue(
                $group->getId(),
                $encryptedKeyId,
                new EncryptedField(new MixedNormalizingField(), new CustomValueObject('second secret'))
            );
            $this->assertTrue(
                $again->getCsstore()->hasDirtyFields(),
                'replacing the value of an encryptedField with such a delegate marks the store dirty'
            );
            $again->save();

            $final = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $final);

            $encryptedValue = $final->getCsstore()->getLocalizedKeyValue($group->getId(), $encryptedKeyId);
            $this->assertInstanceOf(EncryptedField::class, $encryptedValue);
            $plain = $encryptedValue->getPlain();
            $this->assertInstanceOf(CustomValueObject::class, $plain);
            $this->assertSame('second secret', $plain->getText(), 'the replaced secret is the one stored');
        } finally {
            Classificationstore\KeyConfig::getByName(self::PLAIN_MIXED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\KeyConfig::getByName(self::MIXED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\GroupConfig::getByName(self::NO_RESOURCE_FORM_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * Replacing the value of a key whose resource form is NARROWER than what the document holds.
     *
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\StructuredTable::getDataForResource()}
     * answers one entry per configured row and column, so a key defined from the bare type - which
     * has neither - answers `[]` for every value it is handed, while `normalize()` keeps the whole
     * table and the document holds what `normalize()` returns. The resource comparison therefore
     * read every replacement as "unchanged": the save found a clean store and wrote nothing, and the
     * edit was lost without an error.
     *
     * Not the same case as {@see self::testReplacingAValueWithNoResourceFormIsSaved()}: both
     * resource forms here are present and equal, so nothing about two nulls answers it.
     *
     * Nothing but the set is done after the reload - no `setActiveGroups()`, no setter on the value
     * itself - because each of those marks the store dirty on its own and would hide it.
     */
    public function testReplacingAValueWhoseResourceFormIsNarrowerThanTheDocumentIsSaved(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $keyId = $this->keyId($store, 'structuredTable');

        try {
            // precondition: this key's resource form holds nothing at all, whatever the table holds,
            // so two different tables compare equal in it
            $keyDefinition = Classificationstore\Service::getFieldDefinitionFromKeyConfig(
                Classificationstore\KeyConfig::getById($keyId)
            );
            $this->assertInstanceOf(StructuredTableDefinition::class, $keyDefinition);
            $this->assertSame(
                $keyDefinition->getDataForResource(new StructuredTable(['row1' => ['col1' => 'a']])),
                $keyDefinition->getDataForResource(new StructuredTable(['row1' => ['col1' => 'z']]))
            );

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'narrow-resource-form');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue(
                $group->getId(),
                $keyId,
                new StructuredTable(['row1' => ['col1' => 'a', 'col2' => 'b']])
            );
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $reloaded->getCsstore()->setLocalizedKeyValue(
                $group->getId(),
                $keyId,
                new StructuredTable(['row1' => ['col1' => 'c', 'col2' => 'd']])
            );
            $this->assertTrue(
                $reloaded->getCsstore()->hasDirtyFields(),
                'replacing a value its resource form cannot tell apart marks the store dirty'
            );
            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);

            $stored = $again->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(StructuredTable::class, $stored);
            $this->assertSame(
                ['row1' => ['col1' => 'c', 'col2' => 'd']],
                $stored->getData(),
                'the replaced table is the one stored'
            );
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    /**
     * An object holding an encrypted structured value serializes, and is still editable afterwards.
     *
     * {@see \Pimcore\Model\DataObject\Data\EncryptedField::__sleep()} encrypts the plaintext by
     * serializing it in a call of its own, and this store attaches itself as the owner of every
     * owner-aware value it hands out, down to the members of a value, so that a setter on one of
     * them marks the store dirty. Those two together are a cycle the outer serialization cannot
     * see: an owner inside the plaintext leads back to the store, the store holds this field, and
     * the field's `__sleep()` serializes the plaintext again - each nested call a fresh
     * serialization in which nothing has been seen yet. Serializing a version
     * ({@see \Pimcore\Model\Version::save()}, which calls `Serialize::serialize()`) exhausted memory.
     *
     * Detaching the root of the plaintext is not enough, which is what these two key types are here
     * for: a `geopolygon` plaintext is a plain ARRAY of coordinates, so there is no root to detach,
     * and an `imageGallery` plaintext is one object whose MEMBERS are owner-aware.
     *
     * The second half is that the graph comes back. The owner is what makes a later
     * `$gallery->getItems()[0]->setCrop(...)` mark the store dirty, so an object serialized for a
     * version and then edited has to save that edit.
     */
    public function testSerializingAnEncryptedStructuredValueTerminatesAndKeepsItEditable(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::SERIALIZATION_GROUP_NAME);
        $group->save();

        try {
            $polygonKeyId = (int) $this->createEncryptedDelegateKey(
                $store,
                $group,
                self::ENCRYPTED_POLYGON_KEY_NAME,
                'geopolygon',
                new Geopolygon(),
                1
            )->getId();
            $galleryKeyId = (int) $this->createEncryptedDelegateKey(
                $store,
                $group,
                self::ENCRYPTED_GALLERY_KEY_NAME,
                'imageGallery',
                new ImageGalleryDefinition(),
                2
            )->getId();

            $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
            $galleryItem = new Hotspotimage();
            $galleryItem->setImage($asset);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'encrypted-serialization');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $polygonKeyId, new EncryptedField(
                new Geopolygon(),
                [new GeoCoordinates(48.1, 16.1), new GeoCoordinates(48.2, 16.2)]
            ));
            $csField->setLocalizedKeyValue($group->getId(), $galleryKeyId, new EncryptedField(
                new ImageGalleryDefinition(),
                new ImageGallery([$galleryItem])
            ));
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);
            $reloadedField = $reloaded->getCsstore();

            $polygonField = $reloadedField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertInstanceOf(EncryptedField::class, $polygonField);
            $vertices = $polygonField->getPlain();
            $this->assertIsArray($vertices);
            $this->assertInstanceOf(GeoCoordinates::class, $vertices[1]);

            $galleryField = $reloadedField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(EncryptedField::class, $galleryField);
            $gallery = $galleryField->getPlain();
            $this->assertInstanceOf(ImageGallery::class, $gallery);
            $galleryItems = $gallery->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $galleryItems[0]);

            // precondition: without these the test would pass on a plaintext that was never
            // attached to the store, and so would never have led back to it
            $this->assertSame(
                $reloadedField,
                $vertices[1]->_getOwner(),
                'precondition: a vertex is owned by the store'
            );
            $this->assertSame(
                $reloadedField,
                $galleryItems[0]->_getOwner(),
                'precondition: a gallery item is owned by the store'
            );

            $serialized = Serialize::serialize($reloadedField);

            $this->assertIsString($serialized);
            $this->assertStringNotContainsString('16.2', $serialized, 'the coordinates must not be in cleartext');

            // the graph is back, so the store still hears a setter on a member of a plaintext
            $this->assertSame($reloadedField, $vertices[1]->_getOwner(), 'the vertex owner is restored');
            $this->assertSame($reloadedField, $galleryItems[0]->_getOwner(), 'the gallery item owner is restored');

            $reloadedField->resetDirtyMap();
            $vertices[1]->setLatitude(49.9);
            // non-integral, for the reason given in
            // self::testMutatingTheChildOfAReloadedValueThroughItsOwnSettersIsSaved()
            $galleryItems[0]->setCrop(['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5]);

            $this->assertTrue(
                $reloadedField->hasDirtyFields(),
                'a setter on a member of a serialized plaintext still marks the store dirty'
            );

            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);
            $againField = $again->getCsstore();

            $storedPolygon = $againField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertInstanceOf(EncryptedField::class, $storedPolygon);
            $storedVertices = $storedPolygon->getPlain();
            $this->assertIsArray($storedVertices);
            $this->assertInstanceOf(GeoCoordinates::class, $storedVertices[1]);
            $this->assertSame(49.9, $storedVertices[1]->getLatitude(), 'the changed vertex was saved');
            $this->assertSame(48.1, $storedVertices[0]->getLatitude(), 'the untouched vertex is unchanged');

            $storedGallery = $againField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(EncryptedField::class, $storedGallery);
            $storedGalleryValue = $storedGallery->getPlain();
            $this->assertInstanceOf(ImageGallery::class, $storedGalleryValue);
            $storedItems = $storedGalleryValue->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $storedItems[0]);
            $this->assertSame(
                ['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5],
                $storedItems[0]->getCrop(),
                'the crop set after the serialization was saved'
            );
        } finally {
            $storeId = $store->getId();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_POLYGON_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_GALLERY_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\GroupConfig::getByName(self::SERIALIZATION_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * The store that comes BACK from a serialization is editable too: a member of an encrypted
     * plaintext read off the deserialized object still marks that store dirty, and the edit is
     * saved.
     *
     * This is the half the test above cannot cover. There, the original instance is edited and the
     * graph it works on is the one `__sleep()` put back in its `finally`. A version is not read that
     * way: {@see \Pimcore\Model\Version::loadData()} unserializes the stored object and the editor
     * saves THAT, so the plaintext in hand is the one `__wakeup()` decrypted - and `__wakeup()`
     * reconnected only the root of it, with the literal field name `_owner`. A `geopolygon`
     * plaintext has no root to reconnect at all, being a plain array, and an `imageGallery`'s items
     * are members of its root rather than the root. So a vertex and a gallery item came back with no
     * owner, their setters reached nothing, the store stayed clean and
     * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine::save()} wrote
     * nothing: the edit was lost with no error.
     *
     * The field name is asserted beside the owner because an owner on its own reaches nothing:
     * `markMeDirty()` marks the field it names, and `_self` is the one name this store's dirty map
     * uses ({@see \Pimcore\Model\DataObject\Classificationstore::attachOwner()}).
     */
    public function testAnEncryptedStructuredValueOfADeserializedStoreIsStillEditable(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::SERIALIZATION_GROUP_NAME);
        $group->save();

        try {
            $polygonKeyId = (int) $this->createEncryptedDelegateKey(
                $store,
                $group,
                self::ENCRYPTED_POLYGON_KEY_NAME,
                'geopolygon',
                new Geopolygon(),
                1
            )->getId();
            $galleryKeyId = (int) $this->createEncryptedDelegateKey(
                $store,
                $group,
                self::ENCRYPTED_GALLERY_KEY_NAME,
                'imageGallery',
                new ImageGalleryDefinition(),
                2
            )->getId();

            $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
            $galleryItem = new Hotspotimage();
            $galleryItem->setImage($asset);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'encrypted-deserialized');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $polygonKeyId, new EncryptedField(
                new Geopolygon(),
                [new GeoCoordinates(48.1, 16.1), new GeoCoordinates(48.2, 16.2)]
            ));
            $csField->setLocalizedKeyValue($group->getId(), $galleryKeyId, new EncryptedField(
                new ImageGalleryDefinition(),
                new ImageGallery([$galleryItem])
            ));
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            // the object through a serialization and back, which is what reading a version is
            $revived = Serialize::unserialize(Serialize::serialize($reloaded), true);
            $this->assertInstanceOf(Csstore::class, $revived);
            $revived->markAllLazyLoadedKeysAsLoaded();
            $revivedField = $revived->getCsstore();
            $this->assertInstanceOf(Classificationstore::class, $revivedField);

            $polygonField = $revivedField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertInstanceOf(EncryptedField::class, $polygonField);
            $vertices = $polygonField->getPlain();
            $this->assertIsArray($vertices);
            $this->assertInstanceOf(GeoCoordinates::class, $vertices[1]);
            $this->assertSame(16.2, $vertices[1]->getLongitude(), 'the plaintext survived the round trip');

            $galleryField = $revivedField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(EncryptedField::class, $galleryField);
            $gallery = $galleryField->getPlain();
            $this->assertInstanceOf(ImageGallery::class, $gallery);
            $galleryItems = $gallery->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $galleryItems[0]);

            // the owner and the field name it marks, on the deserialized graph
            foreach ([
                'the encrypted polygon field' => $polygonField,
                'a vertex of the decrypted polygon' => $vertices[1],
                'the encrypted gallery field' => $galleryField,
                'the decrypted gallery' => $gallery,
                'an item of the decrypted gallery' => $galleryItems[0],
            ] as $what => $owned) {
                $this->assertSame($revivedField, $owned->_getOwner(), $what . ' is owned by the revived store');
                $this->assertSame('_self', $owned->_getOwnerFieldname(), $what . ' marks `_self`');
            }

            $revivedField->resetDirtyMap();
            $vertices[1]->setLatitude(49.9);
            // non-integral, for the reason given in
            // self::testMutatingTheChildOfAReloadedValueThroughItsOwnSettersIsSaved()
            $galleryItems[0]->setCrop(['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5]);

            $this->assertTrue(
                $revivedField->isFieldDirty('_self'),
                'a setter on a member of a deserialized plaintext marks the store dirty'
            );

            $revived->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);
            $againField = $again->getCsstore();

            $storedPolygon = $againField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertInstanceOf(EncryptedField::class, $storedPolygon);
            $storedVertices = $storedPolygon->getPlain();
            $this->assertIsArray($storedVertices);
            $this->assertInstanceOf(GeoCoordinates::class, $storedVertices[1]);
            $this->assertSame(49.9, $storedVertices[1]->getLatitude(), 'the changed vertex was saved');
            $this->assertSame(48.1, $storedVertices[0]->getLatitude(), 'the untouched vertex is unchanged');

            $storedGallery = $againField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(EncryptedField::class, $storedGallery);
            $storedGalleryValue = $storedGallery->getPlain();
            $this->assertInstanceOf(ImageGallery::class, $storedGalleryValue);
            $storedItems = $storedGalleryValue->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $storedItems[0]);
            $this->assertSame(
                ['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5],
                $storedItems[0]->getCrop(),
                'the crop set on the deserialized gallery item was saved'
            );
        } finally {
            $storeId = $store->getId();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_POLYGON_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_GALLERY_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\GroupConfig::getByName(self::SERIALIZATION_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * A delegate a key is never defined with is refused here too - on a store whose engine holds
     * structured values, which is where the refusal had nothing to stand on.
     *
     * Whether the engine can hold the value and whether a key may carry the type at all are two
     * questions, and only the first of them was asked: a document holds the normalized slug and
     * site id of a `urlSlug` perfectly well, so the capability check
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::assertTypeFitsTheStoreEngine()})
     * passed, and what the key then stored was a slug with no row in the route table
     * `object_url_slugs` behind it - the route is keyed by (object, fieldname), which a key cannot
     * occupy, and that is the whole reason the type is excluded. Wrapping it in an `encryptedField`
     * does not change what the value is, so the delegate is held to the same answer as the type.
     *
     * The second half is the exclusion that is NOT about the value: `calculatedValue` is outside the
     * set this release unlocks, the support core ships for it inside a classification store is
     * untouched, and nothing - here, at write time or through a delegate - refuses it.
     */
    public function testAnEncryptedFieldDelegatingToAnExcludedTypeIsRefusedAndWritesNothing(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::EXCLUDED_DELEGATE_GROUP_NAME);
        $group->save();

        try {
            $slugDefinition = new EncryptedFieldDefinition();
            $slugDefinition->setName(self::ENCRYPTED_SLUG_KEY_NAME);
            $slugDefinition->setDelegateDatatype('urlSlug');
            $slugDefinition->setDelegate(new UrlSlugDefinition());

            $slugKey = new Classificationstore\KeyConfig();
            $slugKey->setStoreId($store->getId());
            $slugKey->setName(self::ENCRYPTED_SLUG_KEY_NAME);
            $slugKey->setType('encryptedField');
            $slugKey->setEnabled(true);
            $slugKey->setDefinition((string) json_encode($slugDefinition));

            try {
                $slugKey->save();
                $this->fail('an encryptedField delegating to urlSlug has to be refused');
            } catch (InvalidConfigException $exception) {
                $this->assertStringContainsString('urlSlug', $exception->getMessage());
            }

            $this->assertNull($slugKey->getId(), 'no row was inserted');
            $this->assertNull($slugKey->getIdentifier(), 'the identifier of a refused key is not consumed');
            $this->assertSame(
                0,
                $this->countKeyRows(self::ENCRYPTED_SLUG_KEY_NAME, $store->getId()),
                'no key row for the refused name'
            );

            $calculatedDelegate = new CalculatedValueDefinition();
            $calculatedDelegate->setCalculatorType(CalculatedValueDefinition::CALCULATOR_TYPE_EXPRESSION);
            $calculatedDelegate->setCalculatorExpression('"computed"');

            $calculatedDefinition = new EncryptedFieldDefinition();
            $calculatedDefinition->setName(self::ENCRYPTED_CALCULATED_KEY_NAME);
            $calculatedDefinition->setDelegateDatatype('calculatedValue');
            $calculatedDefinition->setDelegate($calculatedDelegate);

            $calculatedKey = new Classificationstore\KeyConfig();
            $calculatedKey->setStoreId($store->getId());
            $calculatedKey->setName(self::ENCRYPTED_CALCULATED_KEY_NAME);
            $calculatedKey->setType('encryptedField');
            $calculatedKey->setEnabled(true);
            $calculatedKey->setDefinition((string) json_encode($calculatedDefinition));
            $calculatedKey->save();

            $this->assertNotNull($calculatedKey->getId(), 'a calculatedValue delegate is refused nowhere');
        } finally {
            $storeId = $store->getId();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_SLUG_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_CALCULATED_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\GroupConfig::getByName(self::EXCLUDED_DELEGATE_GROUP_NAME, $storeId)?->delete();
        }
    }

    /**
     * The number of key rows of this name in this store, read from the table rather than through the
     * model, so that a refused key is counted by what was written rather than by what a cache holds.
     */
    private function countKeyRows(string $name, int $storeId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM classificationstore_keys WHERE name = ? AND storeId = ?',
            [$name, $storeId]
        );
    }

    /**
     * A key whose field type does not support inheritance does not read its parent's value, on any
     * of the three paths that reach one.
     *
     * `consent` is the type in the set this release unlocks that declares
     * `supportsInheritance() === false`
     * ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\Consent::supportsInheritance()}): a
     * consent is the record of what one data subject agreed to, so a child object answering "has
     * this one consented" with its parent's grant - and with the id of the audit note behind it -
     * answers with somebody else's answer. A `geopoint` key beside it inherits as any field does,
     * which is what keeps this from asserting that inheritance is simply off.
     */
    public function testAKeyWhoseTypeRefusesInheritanceDoesNotReadItsParentsValue(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $consentKeyId = $this->keyId($store, 'consent');
        $geopointKeyId = $this->keyId($store, 'geopoint');

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $class->setAllowInherit(true);
        $class->save();

        try {
            $parent = new Csstore();
            $parent->setParentId(1);
            $parent->setKey(self::KEY_PREFIX . 'inheritance-parent');
            $parent->setPublished(true);
            $parent->save();

            $parentField = $parent->getCsstore();
            $parentField->setActiveGroups([$group->getId() => true]);
            $parentField->setLocalizedKeyValue($group->getId(), $consentKeyId, new Consent(true, 4711));
            $parentField->setLocalizedKeyValue($group->getId(), $geopointKeyId, new GeoCoordinates(48.2, 16.37));
            $parent->save();

            $child = new Csstore();
            $child->setParentId($parent->getId());
            $child->setKey(self::KEY_PREFIX . 'inheritance-child');
            $child->setPublished(true);
            $child->save();

            $child = Csstore::getById($child->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $child);
            $childField = $child->getCsstore();

            // precondition: a type that does support inheritance reads the parent, so the
            // assertions below are about the type and not about inheritance being off
            $inheritedPoint = $childField->getLocalizedKeyValue($group->getId(), $geopointKeyId);
            $this->assertInstanceOf(GeoCoordinates::class, $inheritedPoint);
            $this->assertSame(48.2, $inheritedPoint->getLatitude());

            // the per-key read
            $this->assertNull(
                $childField->getLocalizedKeyValue($group->getId(), $consentKeyId),
                'a consent key does not read its parent grant'
            );

            // the whole-store read, which merges every ancestor's items
            $items = $childField->getItems();
            $this->assertArrayHasKey($geopointKeyId, $items[$group->getId()] ?? []);
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $items[$group->getId()] ?? [],
                'the parent consent is not merged into the child items'
            );

            // and the editmode payload, which walks to the parent with a recursion of its own
            $payload = $this->getCsstoreFieldDefinition()->getDataForEditmode($childField, $child);
            $language = self::DEFAULT_LANGUAGE;
            $this->assertArrayHasKey($geopointKeyId, $payload['data'][$language][$group->getId()] ?? []);
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $payload['data'][$language][$group->getId()] ?? [],
                'the parent consent is not in the editmode payload'
            );
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $payload['metaData'][$language][$group->getId()] ?? [],
                'and it is not reported as inherited either'
            );

            // the parent itself still holds and reads its own consent
            $storedParent = Csstore::getById($parent->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $storedParent);
            $ownConsent = $storedParent->getCsstore()->getLocalizedKeyValue($group->getId(), $consentKeyId);
            $this->assertInstanceOf(Consent::class, $ownConsent);
            $this->assertTrue($ownConsent->getConsent());
        } finally {
            $class->setAllowInherit(false);
            $class->save();
            $this->dropStructuredFields($store);
        }
    }

    /**
     * Encrypting a consent does not make it inheritable. An `encryptedField` key whose delegate is
     * `consent` refuses its parent's value on the same three paths a plain `consent` key refuses it
     * on.
     *
     * The wrapper is not the type that decides this. It stores, reads, validates and renders
     * whatever its delegate says, and it does not override `supportsInheritance()` - so asking the
     * wrapper got the base class's `true`
     * ({@see \Pimcore\Model\DataObject\ClassDefinition\Data::supportsInheritance()}) where the
     * delegate says `false`
     * ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\Consent::supportsInheritance()}), and a
     * child read its parent's grant and the id of the audit note behind it. All three paths ask
     * {@see \Pimcore\Model\DataObject\Classificationstore\Service::supportsInheritance()}, which
     * answers for the delegate.
     *
     * An encrypted `geopoint` key beside it inherits, which is what keeps this from asserting that
     * encryption simply turns inheritance off.
     */
    public function testAnEncryptedKeyWhoseDelegateRefusesInheritanceDoesNotReadItsParentsValue(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::ENCRYPTED_GROUP_NAME);
        $group->save();

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $class->setAllowInherit(true);
        $class->save();

        try {
            $consentKeyId = (int) $this->createEncryptedDelegateKey(
                $store,
                $group,
                self::ENCRYPTED_CONSENT_KEY_NAME,
                'consent',
                new ConsentDefinition(),
                1
            )->getId();
            $geopointKeyId = (int) $this->createEncryptedGeopointKey($store, $group)->getId();

            $parent = new Csstore();
            $parent->setParentId(1);
            $parent->setKey(self::KEY_PREFIX . 'encrypted-inheritance-parent');
            $parent->setPublished(true);
            $parent->save();

            $parentField = $parent->getCsstore();
            $parentField->setActiveGroups([$group->getId() => true]);
            $parentField->setLocalizedKeyValue(
                $group->getId(),
                $consentKeyId,
                new EncryptedField(new ConsentDefinition(), new Consent(true, 4711))
            );
            $parentField->setLocalizedKeyValue(
                $group->getId(),
                $geopointKeyId,
                new EncryptedField(new Geopoint(), new GeoCoordinates(48.2, 16.37))
            );
            $parent->save();

            $child = new Csstore();
            $child->setParentId($parent->getId());
            $child->setKey(self::KEY_PREFIX . 'encrypted-inheritance-child');
            $child->setPublished(true);
            $child->save();

            $child = Csstore::getById($child->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $child);
            $childField = $child->getCsstore();

            // precondition: an encrypted key of a type that does support inheritance reads the
            // parent, so the assertions below are about the delegate and not about encryption
            $inheritedPoint = $childField->getLocalizedKeyValue($group->getId(), $geopointKeyId);
            $this->assertInstanceOf(EncryptedField::class, $inheritedPoint);
            $this->assertInstanceOf(GeoCoordinates::class, $inheritedPoint->getPlain());
            $this->assertSame(48.2, $inheritedPoint->getPlain()->getLatitude());

            // the per-key read
            $this->assertNull(
                $childField->getLocalizedKeyValue($group->getId(), $consentKeyId),
                'an encrypted consent key does not read its parent grant'
            );

            // the whole-store read, which merges every ancestor's items
            $items = $childField->getItems();
            $this->assertArrayHasKey($geopointKeyId, $items[$group->getId()] ?? []);
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $items[$group->getId()] ?? [],
                'the parent encrypted consent is not merged into the child items'
            );

            // and the editmode payload, which walks to the parent with a recursion of its own
            $payload = $this->getCsstoreFieldDefinition()->getDataForEditmode($childField, $child);
            $language = self::DEFAULT_LANGUAGE;
            $this->assertArrayHasKey($geopointKeyId, $payload['data'][$language][$group->getId()] ?? []);
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $payload['data'][$language][$group->getId()] ?? [],
                'the parent encrypted consent is not in the editmode payload'
            );
            $this->assertArrayNotHasKey(
                $consentKeyId,
                $payload['metaData'][$language][$group->getId()] ?? [],
                'and it is not reported as inherited either'
            );

            // the parent itself still holds and reads its own encrypted consent
            $storedParent = Csstore::getById($parent->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $storedParent);
            $ownConsent = $storedParent->getCsstore()->getLocalizedKeyValue($group->getId(), $consentKeyId);
            $this->assertInstanceOf(EncryptedField::class, $ownConsent);
            $this->assertInstanceOf(Consent::class, $ownConsent->getPlain());
            $this->assertTrue($ownConsent->getPlain()->getConsent());
            $this->assertSame(4711, $ownConsent->getPlain()->getNoteId());
        } finally {
            $class->setAllowInherit(false);
            $class->save();
            $storeId = $store->getId();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_CONSENT_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_KEY_NAME, $storeId, true)?->delete();
            Classificationstore\GroupConfig::getByName(self::ENCRYPTED_GROUP_NAME, $storeId)?->delete();
        }
    }

    /**
     * A value of a structured type is handed out as the object that is stored, and is as readily
     * changed through its own setters as it is set again. Such a change has to reach the store's
     * dirty flag, or the engine finds a clean store and writes nothing: the edit is lost silently,
     * which is what no owner on the value produces.
     */
    public function testMutatingAReloadedValueThroughItsOwnSettersIsSaved(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $keyId = $this->keyId($store, 'geopoint');

        try {
            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'mutate-after-reload');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $keyId, new GeoCoordinates(48.2, 16.37));
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $coordinates = $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(GeoCoordinates::class, $coordinates);

            // the one thing this test is about: no setLocalizedKeyValue() call follows
            $coordinates->setLatitude(51.5);
            $this->assertTrue(
                $reloaded->getCsstore()->hasDirtyFields(),
                'mutating a loaded value has to mark the store dirty'
            );

            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);

            $stored = $again->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(GeoCoordinates::class, $stored);
            $this->assertSame(51.5, $stored->getLatitude());
            $this->assertSame(16.37, $stored->getLongitude());
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    /**
     * The same, one level down: the value a key hands out is often not the only thing with setters
     * on it.
     *
     * A `geopolygon` is a plain array of {@see GeoCoordinates} and the key hands the array out, and
     * an `imageGallery` hands out {@see ImageGallery} whose items are {@see Hotspotimage} objects.
     * Each of those children carries its own setters and its own `markMeDirty()`, so a store that
     * attached itself to the outer value alone would take `$polygon[1]->setLatitude()` or
     * `$gallery->getItems()[0]->setCrop()` and find no owner to report to: the store stays clean and
     * the engine writes nothing, which is the edit disappearing without an error.
     *
     * The two are the shapes the walk has to reach differently - through a bare array the key hands
     * out, and through an array held in a protected property. A `hotspotimage` rides along as the
     * control: it is the outer value itself, so it was already covered, and it has to keep working.
     * The remaining shape, a property holding one owner-aware value, is
     * {@see self::testMutatingThePlainValueOfAReloadedEncryptedFieldIsSaved()}.
     */
    public function testMutatingTheChildOfAReloadedValueThroughItsOwnSettersIsSaved(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $polygonKeyId = $this->keyId($store, 'geopolygon');
        $galleryKeyId = $this->keyId($store, 'imageGallery');
        $hotspotKeyId = $this->keyId($store, 'hotspotimage');

        try {
            $asset = TestHelper::createImageAsset(self::KEY_PREFIX);

            $galleryItem = new Hotspotimage();
            $galleryItem->setImage($asset);

            $hotspotimage = new Hotspotimage();
            $hotspotimage->setImage($asset);

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'mutate-child-after-reload');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue($group->getId(), $polygonKeyId, [
                new GeoCoordinates(48.1, 16.1),
                new GeoCoordinates(48.2, 16.2),
            ]);
            $csField->setLocalizedKeyValue($group->getId(), $galleryKeyId, new ImageGallery([$galleryItem]));
            $csField->setLocalizedKeyValue($group->getId(), $hotspotKeyId, $hotspotimage);
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);
            $reloadedField = $reloaded->getCsstore();

            // a member of the array a geopolygon key hands out
            $polygon = $reloadedField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertIsArray($polygon);
            $this->assertInstanceOf(GeoCoordinates::class, $polygon[1]);

            // an item of the gallery, reached through the protected property holding them
            $gallery = $reloadedField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(ImageGallery::class, $gallery);
            $galleryItems = $gallery->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $galleryItems[0]);

            // the one value a hotspotimage key hands out, whose setters are its own
            $hotspot = $reloadedField->getLocalizedKeyValue($group->getId(), $hotspotKeyId);
            $this->assertInstanceOf(Hotspotimage::class, $hotspot);

            // the one thing this test is about: no setLocalizedKeyValue() call follows any of these
            $polygon[1]->setLatitude(49.9);

            // the crop numbers are deliberately not integral: a crop is a plain array that reaches
            // the document as it stands, and JSON draws no line between the float `11.0` and the
            // int `11`, so an integral float comes back as an int and says nothing about whether
            // the edit was saved. Non-integral values keep the comparison below an identity one
            $galleryItems[0]->setCrop(['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5]);
            $hotspot->setHotspots([['name' => 'spot', 'top' => 1.5, 'left' => 2.5, 'width' => 3.5, 'height' => 4.5]]);

            $this->assertTrue(
                $reloadedField->hasDirtyFields(),
                'mutating a child of a loaded value has to mark the store dirty'
            );

            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);
            $againField = $again->getCsstore();

            $storedPolygon = $againField->getLocalizedKeyValue($group->getId(), $polygonKeyId);
            $this->assertIsArray($storedPolygon);
            $this->assertInstanceOf(GeoCoordinates::class, $storedPolygon[1]);
            $this->assertSame(49.9, $storedPolygon[1]->getLatitude(), 'the changed polygon vertex was saved');
            $this->assertSame(16.2, $storedPolygon[1]->getLongitude());
            $this->assertInstanceOf(GeoCoordinates::class, $storedPolygon[0]);
            $this->assertSame(48.1, $storedPolygon[0]->getLatitude(), 'the untouched vertex is unchanged');

            $storedGallery = $againField->getLocalizedKeyValue($group->getId(), $galleryKeyId);
            $this->assertInstanceOf(ImageGallery::class, $storedGallery);
            $storedItems = $storedGallery->getItems();
            $this->assertInstanceOf(Hotspotimage::class, $storedItems[0]);
            $this->assertSame(
                ['cropWidth' => 11.5, 'cropHeight' => 22.5, 'cropTop' => 3.5, 'cropLeft' => 4.5],
                $storedItems[0]->getCrop(),
                'the crop set on a gallery item was saved'
            );

            $storedHotspot = $againField->getLocalizedKeyValue($group->getId(), $hotspotKeyId);
            $this->assertInstanceOf(Hotspotimage::class, $storedHotspot);
            $this->assertSame(
                [['name' => 'spot', 'top' => 1.5, 'left' => 2.5, 'width' => 3.5, 'height' => 4.5]],
                $storedHotspot->getHotspots(),
                'the hotspots set on the value were saved'
            );
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    /**
     * The plain value inside an `encryptedField`, which is the one owner-aware child that is reached
     * through a property rather than through a container's items, and the one whose loss would be
     * invisible: the ciphertext in the document changes on every write, so nothing about the stored
     * row would look stale.
     */
    public function testMutatingThePlainValueOfAReloadedEncryptedFieldIsSaved(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::ENCRYPTED_GROUP_NAME);
        $group->save();

        try {
            $keyConfig = $this->createEncryptedGeopointKey($store, $group);
            $keyId = (int) $keyConfig->getId();

            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'mutate-encrypted-after-reload');
            $object->setPublished(true);
            $object->save();

            $csField = $object->getCsstore();
            $csField->setActiveGroups([$group->getId() => true]);
            $csField->setLocalizedKeyValue(
                $group->getId(),
                $keyId,
                new EncryptedField(new Geopoint(), new GeoCoordinates(48.2, 16.37))
            );
            $object->save();

            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $loaded = $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(EncryptedField::class, $loaded);
            $coordinates = $loaded->getPlain();
            $this->assertInstanceOf(GeoCoordinates::class, $coordinates);

            // the one thing this test is about: no setLocalizedKeyValue() and no setPlain() follows
            $coordinates->setLatitude(51.5);

            $this->assertTrue(
                $reloaded->getCsstore()->hasDirtyFields(),
                'mutating the plain value of a loaded encrypted field has to mark the store dirty'
            );

            $reloaded->save();

            $again = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $again);

            $stored = $again->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(EncryptedField::class, $stored);
            $storedCoordinates = $stored->getPlain();
            $this->assertInstanceOf(GeoCoordinates::class, $storedCoordinates);
            $this->assertSame(51.5, $storedCoordinates->getLatitude());
            $this->assertSame(16.37, $storedCoordinates->getLongitude());
        } finally {
            Classificationstore\KeyConfig::getByName(self::ENCRYPTED_KEY_NAME, $store->getId(), true)?->delete();
            Classificationstore\GroupConfig::getByName(self::ENCRYPTED_GROUP_NAME, $store->getId())?->delete();
        }
    }

    /**
     * The editor's payload reaches a key type through
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::getDataFromEditmode()},
     * and `consent` needs two things from it that a bare payload does not carry: the object, which
     * the audit note is written against, and what the key held before, which decides whether a note
     * is written at all. A key name is not a class field, so the object has no getter to answer the
     * second with.
     */
    public function testAConsentKeyConvertsAnEditorPayloadAndWritesOneNotePerChange(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        $group = $this->configureStoreWithStructuredFields($store);
        $keyId = $this->keyId($store, 'consent');
        $fieldDefinition = $this->getCsstoreFieldDefinition();

        try {
            $object = new Csstore();
            $object->setParentId(1);
            $object->setKey(self::KEY_PREFIX . 'consent-editmode');
            $object->setPublished(true);
            $object->save();

            $granted = $fieldDefinition->getDataFromEditmode(
                $this->consentPayload($group->getId(), $keyId, true),
                $object
            );
            $consent = $granted->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(Consent::class, $consent);
            $this->assertTrue($consent->getConsent());
            $this->assertNotNull($consent->getNoteId(), 'granting consent writes the audit note');

            $object->setCsstore($granted);
            $object->save();

            // the same payload again: the value did not change, so the note of the change stands
            // rather than a second one being written
            $reloaded = Csstore::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Csstore::class, $reloaded);

            $unchanged = $fieldDefinition->getDataFromEditmode(
                $this->consentPayload($group->getId(), $keyId, true),
                $reloaded
            );
            $secondConsent = $unchanged->getLocalizedKeyValue($group->getId(), $keyId);
            $this->assertInstanceOf(Consent::class, $secondConsent);
            $this->assertSame(
                $consent->getNoteId(),
                $secondConsent->getNoteId(),
                'an unchanged consent keeps the note of the change that set it'
            );
        } finally {
            $this->dropStructuredFields($store);
        }
    }

    /**
     * What the object editor posts for one consent key of one group.
     *
     * @return array<string, mixed>
     */
    private function consentPayload(int $groupId, int $keyId, bool $consent): array
    {
        return [
            'data' => [self::DEFAULT_LANGUAGE => [$groupId => [$keyId => $consent]]],
            'activeGroups' => [$groupId => true],
            'groupCollectionMapping' => [],
        ];
    }

    private function getCsstoreFieldDefinition(): ClassificationstoreField
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertNotNull($class);

        $fieldDefinition = $class->getFieldDefinition(self::FIELDNAME);
        $this->assertInstanceOf(ClassificationstoreField::class, $fieldDefinition);

        return $fieldDefinition;
    }

    /**
     * An `encryptedField` key whose delegate is the test type that normalizes a value to whatever
     * shape it has, and for which no classificationstore marshaller is registered - so the engine
     * itself decides whether the value needs the JSON envelope.
     */
    /**
     * A key of the mixed-normalizing type itself: a field type that normalizes and denormalizes but
     * is not {@see \Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface},
     * which the document engine holds without a resource form of any kind.
     */
    private function createPlainMixedNormalizingKey(
        Classificationstore\StoreConfig $store,
        Classificationstore\GroupConfig $group
    ): Classificationstore\KeyConfig {
        $definition = new MixedNormalizingField();
        $definition->setName(self::PLAIN_MIXED_KEY_NAME);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::PLAIN_MIXED_KEY_NAME);
        $keyConfig->setType('mixedNormalizingObject');
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition((string) json_encode($definition));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(1);
        $relation->save();

        return $keyConfig;
    }

    private function createMixedNormalizingKey(
        Classificationstore\StoreConfig $store,
        Classificationstore\GroupConfig $group
    ): Classificationstore\KeyConfig {
        $definition = new EncryptedFieldDefinition();
        $definition->setName(self::MIXED_KEY_NAME);
        $definition->setDelegateDatatype('mixedNormalizingObject');
        $definition->setDelegate(new MixedNormalizingField());

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::MIXED_KEY_NAME);
        $keyConfig->setType('encryptedField');
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition((string) json_encode($definition));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(1);
        $relation->save();

        return $keyConfig;
    }

    /**
     * Sets the plain value of an `encryptedField` key on a freshly loaded object and saves it, which
     * is what makes the save compare the new encoding against the stored one.
     */
    private function setPlainValue(
        Csstore $object,
        Classificationstore\GroupConfig $group,
        int $keyId,
        mixed $plain
    ): void {
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Csstore::class, $reloaded);

        $reloaded->getCsstore()->setLocalizedKeyValue(
            $group->getId(),
            $keyId,
            new EncryptedField(new MixedNormalizingField(), $plain)
        );
        $reloaded->save();
    }

    private function readPlainValue(
        Csstore $object,
        Classificationstore\GroupConfig $group,
        int $keyId
    ): mixed {
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Csstore::class, $reloaded);

        $loaded = $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyId);
        $this->assertInstanceOf(EncryptedField::class, $loaded);

        return $loaded->getPlain();
    }

    /**
     * Whether the stored entry of this key says its value is a JSON envelope rather than the value
     * itself - the qualifier the engine writes beside the encrypted bytes, read straight out of the
     * document because that is the storage-level fact under test.
     */
    private function entryClaimsAnEnvelope(
        Csstore $object,
        Classificationstore\GroupConfig $group,
        int $keyId
    ): bool {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertNotNull($class);

        $stored = (string) Db::get()->fetchOne(
            'SELECT `values` FROM ' . self::DOCUMENT_TABLE . $class->getId()
            . ' WHERE id = ? AND fieldname = ? AND groupId = ?',
            [$object->getId(), self::FIELDNAME, $group->getId()]
        );

        $document = json_decode($stored, true);
        $this->assertIsArray($document);

        $entries = $document['k'][$keyId] ?? null;
        $this->assertIsArray($entries, 'the document holds an entry for the key');
        $this->assertIsArray($entries[0] ?? null);

        return ($entries[0]['j'] ?? null) === true;
    }

    private function createEncryptedGeopointKey(
        Classificationstore\StoreConfig $store,
        Classificationstore\GroupConfig $group
    ): Classificationstore\KeyConfig {
        return $this->createEncryptedDelegateKey(
            $store,
            $group,
            self::ENCRYPTED_KEY_NAME,
            'geopoint',
            new Geopoint(),
            1
        );
    }

    /**
     * An `encryptedField` key delegating to the given type.
     */
    private function createEncryptedDelegateKey(
        Classificationstore\StoreConfig $store,
        Classificationstore\GroupConfig $group,
        string $keyName,
        string $delegateType,
        ClassDefinition\Data $delegate,
        int $sorter
    ): Classificationstore\KeyConfig {
        $definition = new EncryptedFieldDefinition();
        $definition->setName($keyName);
        $definition->setDelegateDatatype($delegateType);
        $definition->setDelegate($delegate);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName($keyName);
        $keyConfig->setType('encryptedField');
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition((string) json_encode($definition));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter($sorter);
        $relation->save();

        return $keyConfig;
    }

    /**
     * One value per structured type, keyed by the key name the fixture defines for it.
     *
     * @return array<string, mixed>
     */
    private function structuredValues(): array
    {
        $asset = TestHelper::createImageAsset(self::KEY_PREFIX);
        $video = TestHelper::createVideoAsset(self::KEY_PREFIX);
        $document = TestHelper::createEmptyDocumentPage(self::KEY_PREFIX);

        $hotspotimage = new Hotspotimage();
        $hotspotimage->setImage($asset);
        $hotspotimage->setHotspots([['name' => 'spot', 'top' => 1.5, 'left' => 2.5, 'width' => 3.5, 'height' => 4.5]]);
        $hotspotimage->setMarker([['name' => 'marker', 'top' => 5.5, 'left' => 6.5]]);
        // non-integral for the same reason as the crop in
        // self::testMutatingTheChildOfAReloadedValueThroughItsOwnSettersIsSaved(): nothing in a
        // document test should rest on JSON telling an integral float from an int
        $hotspotimage->setCrop(['cropWidth' => 10.5, 'cropHeight' => 20.5, 'cropTop' => 1.5, 'cropLeft' => 2.5]);

        $galleryItem = new Hotspotimage();
        $galleryItem->setImage($asset);

        $link = new Link();
        $link->setText('the page');
        $link->setLinktype('internal');
        $link->setInternalType('document');
        $link->setInternal($document->getId());

        $videoValue = new Video();
        $videoValue->setType('asset');
        $videoValue->setTitle('the video');
        $videoValue->setData($video);
        $videoValue->setPoster($asset);

        return [
            'consent' => new Consent(true, 4711),
            'dateRange' => CarbonPeriod::create(new Carbon('2026-10-29'), new Carbon('2026-11-05')),
            'externalImage' => new ExternalImage('https://www.pimcore.com/image.jpg'),
            'geobounds' => new Geobounds(new GeoCoordinates(48.3, 16.4), new GeoCoordinates(48.1, 16.2)),
            'geopoint' => new GeoCoordinates(48.2, 16.37),
            'geopolygon' => [
                new GeoCoordinates(48.1, 16.1),
                new GeoCoordinates(48.2, 16.2),
                new GeoCoordinates(48.3, 16.3),
            ],
            'geopolyline' => [new GeoCoordinates(47.1, 15.1), new GeoCoordinates(47.2, 15.2)],
            'hotspotimage' => $hotspotimage,
            'imageGallery' => new ImageGallery([$galleryItem]),
            'link' => $link,
            'numericRange' => new NumericRange(1.5, 9.5),
            'structuredTable' => new StructuredTable(['row1' => ['col1' => 'a', 'col2' => 'b']]),
            'video' => $videoValue,
        ];
    }

    private function keyId(Classificationstore\StoreConfig $store, string $keyName): int
    {
        $key = Classificationstore\KeyConfig::getByName($keyName, $store->getId());
        $this->assertNotNull($key, $keyName . ' has to be defined');

        return (int) $key->getId();
    }

    private function normalize(
        Classificationstore\StoreConfig $store,
        string $keyName,
        mixed $value,
        Csstore $object
    ): mixed {
        $fieldDefinition = $this->fieldDefinition($store, $keyName);

        return $fieldDefinition->normalize($value, [
            'object' => $object,
            'fieldDefinition' => $fieldDefinition,
            'fieldname' => self::FIELDNAME,
        ]);
    }

    private function fieldDefinition(Classificationstore\StoreConfig $store, string $keyName): NormalizerInterface
    {
        $key = Classificationstore\KeyConfig::getByName($keyName, $store->getId());
        $this->assertNotNull($key);

        $fieldDefinition = Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);
        $this->assertInstanceOf(NormalizerInterface::class, $fieldDefinition);

        return $fieldDefinition;
    }

    /**
     * A document written before the qualifiers existed carries only the language and the value, and
     * reads back as the default variant at position 0 - so the envelope version does not move and
     * nothing has to be migrated. A save that changes nothing about that group does not rewrite it.
     */
    public function testADocumentWrittenBeforeTheQualifiersExistedReadsBackUnchanged(): void
    {
        $groupId = Classificationstore\GroupConfig::getByName('testgroup1')->getId();
        $otherGroupId = Classificationstore\GroupConfig::getByName('testgroup2')->getId();
        $keyId = $this->inputKeyId();
        $otherKeyId = $this->textareaKeyId();

        $object = $this->createObject('legacy');
        $object->save();

        $legacyDocument = '{"v":1,"k":{"' . $keyId . '":[{"d":"legacy"},{"l":"en","d":"legacy-en"}]}}';

        // `values` is a reserved word, so the columns are named with their quoting here
        Db::get()->executeStatement(
            'INSERT INTO ' . $this->documentTable()
            . ' (`id`, `fieldname`, `groupId`, `collectionId`, `values`, `checksum`)'
            . ' VALUES (?, ?, ?, NULL, ?, NULL)',
            [$object->getId(), self::FIELDNAME, $groupId, $legacyDocument]
        );

        $object = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertSame('legacy', $object->getCsstore()->getLocalizedKeyValue($groupId, $keyId));
        $this->assertSame('legacy-en', $object->getCsstore()->getLocalizedKeyValue($groupId, $keyId, 'en'));
        $this->assertSame(['legacy'], $object->getCsstore()->getKeyValues($groupId, $keyId));
        $this->assertSame(['legacy-en'], $object->getCsstore()->getKeyValues($groupId, $keyId, 'en'));

        // a save that writes another group, so that the diff really does serialize this group's
        // legacy document and compare it - a save that wrote nothing at all would prove nothing
        $object->getCsstore()->setActiveGroups(
            $object->getCsstore()->getActiveGroups() + [$otherGroupId => true]
        );
        $object->getCsstore()->setLocalizedKeyValue($otherGroupId, $otherKeyId, 'elsewhere');
        $object->save();

        $row = $this->storedRow($object->getId(), $groupId);

        $this->assertSame($legacyDocument, $row['values'], 'the legacy document was not rewritten');
        $this->assertNull($row['checksum'], 'and no writer stamped it');
    }

    /**
     * A list keeps its order through a save and a load, including after a value in the middle is
     * replaced - the document holds one entry per position, and the positions it holds are the ones
     * the list is read back in.
     */
    public function testAListKeepsItsOrderThroughASaveAndALoad(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([], true);

        $object = $this->createObject('list');
        $object->getCsstore()->setKeyValues($groupId, $keyId, $this->listEntries(['red', 'blue', 'green']));
        $object->save();

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(['red', 'blue', 'green'], $object->getCsstore()->getKeyValues($groupId, $keyId));

        $object->getCsstore()->setKeyValues($groupId, $keyId, $this->listEntries(['red', 'teal', 'green']));
        $object->save();

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(['red', 'teal', 'green'], $object->getCsstore()->getKeyValues($groupId, $keyId));
        $this->assertSame(
            'red',
            $object->getCsstore()->getLocalizedKeyValue($groupId, $keyId),
            'the documented getter answers position 0 and not the whole list'
        );

        $positions = array_map(
            static fn (array $entry): int => (int) ($entry['p'] ?? 0),
            $this->storedEntries($object->getId(), $groupId, $keyId)
        );

        sort($positions);
        $this->assertSame([0, 1, 2], $positions);
    }

    /**
     * A list whose first value is cleared keeps the positions of the values behind it, through the
     * save and through the load: the document holds the positions it was given rather than closing
     * the gap, so the second value is still read as the second.
     */
    public function testAListWhoseFirstValueIsClearedKeepsThePositionsOfTheOthers(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([], true);

        $object = $this->createObject('cleared');
        $object->getCsstore()->setKeyValues($groupId, $keyId, $this->listEntries(['red', 'blue', 'green']));
        $object->save();

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('blue', null, null, 1),
            new MultiValue('green', null, null, 2),
        ]);
        $object->save();

        $positions = array_map(
            static fn (array $entry): int => (int) ($entry['p'] ?? 0),
            $this->storedEntries($object->getId(), $groupId, $keyId)
        );

        sort($positions);
        $this->assertSame([1, 2], $positions, 'the cleared position is gone and the others stayed where they were');

        $object = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertSame([1 => 'blue', 2 => 'green'], $object->getCsstore()->getKeyValues($groupId, $keyId));
        $this->assertNull(
            $object->getCsstore()->getLocalizedKeyValue($groupId, $keyId),
            'the documented getter answers position 0, which the list no longer holds'
        );
    }

    /**
     * Each variant of a key is written and read on its own: a save that changes one leaves the
     * stored value of the other byte for byte as it was, and leaves the documents of the other
     * groups alone - which is what one document per group buys.
     */
    public function testEachVariantOfAKeyIsWritableOnItsOwn(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([self::METRIC, self::IMPERIAL], false);
        $otherGroupId = Classificationstore\GroupConfig::getByName('testgroup1')->getId();
        $otherKeyId = $this->inputKeyId();

        $object = $this->createObject('variants');
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('1.80 m', null, self::METRIC),
            new MultiValue('5 ft 11 in', null, self::IMPERIAL),
        ]);
        $object->getCsstore()->setLocalizedKeyValue($otherGroupId, $otherKeyId, 'untouched');
        $object->save();

        $entries = $this->storedEntries($object->getId(), $groupId, $keyId);
        $this->assertCount(2, $entries, 'the key carries one entry per variant in its group document');

        $storedMetric = $this->entryOf($entries, self::METRIC);
        $otherGroupRow = $this->storedRow($object->getId(), $otherGroupId);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $object->getCsstore()->setKeyValues($groupId, $keyId, [new MultiValue('6 ft 0 in', null, self::IMPERIAL)]);
        $object->save();

        $entries = $this->storedEntries($object->getId(), $groupId, $keyId);

        $this->assertSame(
            $storedMetric,
            $this->entryOf($entries, self::METRIC),
            'the metric entry is byte-identical after a save that changed only the imperial one'
        );
        $this->assertSame('6 ft 0 in', $this->entryOf($entries, self::IMPERIAL)['d']);
        $this->assertSame(
            $otherGroupRow,
            $this->storedRow($object->getId(), $otherGroupId),
            'the document of the other group this object holds was not rewritten'
        );

        $object = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertSame(['1.80 m'], $object->getCsstore()->getKeyValues($groupId, $keyId, null, self::METRIC));
        $this->assertSame(['6 ft 0 in'], $object->getCsstore()->getKeyValues($groupId, $keyId, null, self::IMPERIAL));
    }

    /**
     * Narrowing a declaration hides the values it stops permitting and deletes none of them: an
     * ordinary save afterwards still writes them, so widening the declaration again brings them
     * back.
     */
    public function testNarrowingADeclarationKeepsTheValuesItHides(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([self::METRIC, self::IMPERIAL], false);

        $object = $this->createObject('narrowing');
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('1.80 m', null, self::METRIC),
            new MultiValue('5 ft 11 in', null, self::IMPERIAL),
        ]);
        $object->save();

        $this->narrowMultiValueKey([self::METRIC], false);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(
            [],
            $object->getCsstore()->getKeyValues($groupId, $keyId, null, self::IMPERIAL),
            'a variant the key no longer declares reads as nothing'
        );

        // a save that does rewrite the group's document, so that the hidden value is not merely
        // surviving a save that wrote nothing at all
        $object->getCsstore()->setKeyValues($groupId, $keyId, [new MultiValue('1.81 m', null, self::METRIC)]);
        $object->save();

        $entries = $this->storedEntries($object->getId(), $groupId, $keyId);
        $this->assertSame('5 ft 11 in', $this->entryOf($entries, self::IMPERIAL)['d']);
        $this->assertSame('1.81 m', $this->entryOf($entries, self::METRIC)['d']);

        $this->narrowMultiValueKey([self::METRIC, self::IMPERIAL], false);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(
            ['5 ft 11 in'],
            $object->getCsstore()->getKeyValues($groupId, $keyId, null, self::IMPERIAL),
            'widening the declaration again makes the value readable'
        );
    }

    /**
     * The same rule on the WRITE path, which is where it was broken: a key that no longer declares a
     * list permits a write to position 0 and to nothing else, so the positions the narrowed
     * declaration hides are not that write's to remove.
     *
     * Store positions 0 and 1, take the list declaration away, edit position 0 through
     * setKeyValues() and save: the write replaced the whole slot, the save wrote it without position
     * 1, and widening the declaration again could never bring that value back. Narrowing HIDES
     * values and deletes none of them, on both paths.
     */
    public function testNarrowingAListAndThenEditingItsFirstValueKeepsTheHiddenPositions(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([], true);

        $object = $this->createObject('narrowing-write');
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('1.80 m', null, null, 0),
            new MultiValue('1.90 m', null, null, 1),
        ]);
        $object->save();

        $this->narrowMultiValueKey([], false);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(
            ['1.80 m'],
            $object->getCsstore()->getKeyValues($groupId, $keyId),
            'a key that no longer declares a list reads its first value alone'
        );

        // the write the editor of a narrowed key makes, followed by the save that rewrites the group
        $object->getCsstore()->setKeyValues($groupId, $keyId, [new MultiValue('1.81 m')]);
        $object->save();

        $entries = $this->storedEntries($object->getId(), $groupId, $keyId);

        $this->assertSame(
            ['1.81 m', '1.90 m'],
            array_map(static fn (array $entry): mixed => $entry['d'], $entries),
            'the hidden position is still in the document the save wrote'
        );

        $this->narrowMultiValueKey([], true);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $this->assertSame(
            [0 => '1.81 m', 1 => '1.90 m'],
            $object->getCsstore()->getKeyValues($groupId, $keyId),
            'widening the declaration again shows the position the narrowed one hid'
        );
    }

    /**
     * And the same rule on the SEARCH INDEX, which is the one surface a classification store value
     * is filterable from at all - its values never reach `object_query_<CLASS>`.
     *
     * A value hidden by a narrowed declaration is still stored, and still carries its dependency
     * rows and cache tags, because narrowing deletes nothing. The index is a different question: a
     * value the object does not show must not be what a search finds it by. Read off the raw
     * qualified tree, the index kept every hidden variant and position after the next indexing pass.
     */
    public function testNarrowingADeclarationKeepsTheValuesItHidesOutOfTheSearchIndex(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([self::METRIC, self::IMPERIAL], true);

        $object = $this->createObject('narrowing-index');
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('1.80 m', null, self::METRIC, 0),
            new MultiValue('1.90 m', null, self::METRIC, 1),
            new MultiValue('5 ft 11 in', null, self::IMPERIAL, 0),
        ]);
        $object->save();

        $indexed = $this->searchIndexData($object);

        $this->assertStringContainsString('1.80 m', $indexed);
        $this->assertStringContainsString('1.90 m', $indexed, 'a declared position above zero is indexed');
        $this->assertStringContainsString('5 ft 11 in', $indexed, 'a declared variant is indexed');

        // the variant goes and the list is switched off, which hides both of the others
        $this->narrowMultiValueKey([self::METRIC], false);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $indexed = $this->searchIndexData($object);

        $this->assertStringContainsString('1.80 m', $indexed, 'the one value the narrowed key shows is indexed');
        $this->assertStringNotContainsString(
            '5 ft 11 in',
            $indexed,
            'a variant the key no longer declares reads as nothing, so it is findable as nothing'
        );
        $this->assertStringNotContainsString(
            '1.90 m',
            $indexed,
            'a position of a list the key no longer declares is hidden from the index the same way'
        );

        // and none of it was deleted: the document still carries all three, which is what makes
        // widening the declaration again bring them back
        $this->assertCount(3, $this->storedEntries($object->getId(), $groupId, $keyId));

        $this->narrowMultiValueKey([self::METRIC, self::IMPERIAL], true);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $indexed = $this->searchIndexData($object);

        $this->assertStringContainsString('5 ft 11 in', $indexed, 'widening makes the value findable again');
        $this->assertStringContainsString('1.90 m', $indexed);
    }

    /**
     * The declaration that declines the unqualified value hides it the way a removed variant is
     * hidden: out of the index and out of getKeyValues(), with nothing deleted, so permitting it
     * again brings it back.
     */
    public function testDecliningTheUnqualifiedValueHidesItAndPermittingItAgainShowsIt(): void
    {
        [$groupId, $keyId] = $this->createMultiValueKey([self::METRIC], false);

        $object = $this->createObject('declining-unqualified');
        $object->getCsstore()->setKeyValues($groupId, $keyId, [
            new MultiValue('unqualified value'),
            new MultiValue('1.80 m', null, self::METRIC),
        ]);
        $object->save();

        $this->assertStringContainsString('unqualified value', $this->searchIndexData($object));

        $this->narrowMultiValueKey([self::METRIC], false, false);

        $object = Csstore::getById($object->getId(), ['force' => true]);
        $object->save();

        $this->assertSame([], $object->getCsstore()->getKeyValues($groupId, $keyId));
        $this->assertStringNotContainsString('unqualified value', $this->searchIndexData($object));
        $this->assertStringContainsString('1.80 m', $this->searchIndexData($object));
        $this->assertCount(2, $this->storedEntries($object->getId(), $groupId, $keyId));

        $this->narrowMultiValueKey([self::METRIC], false, true);

        $object = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertSame(['unqualified value'], $object->getCsstore()->getKeyValues($groupId, $keyId));
        $this->assertStringContainsString('unqualified value', $this->searchIndexData($object));
    }

    /**
     * The two readers of position 0 of the default variant, on one fixture. getLocalizedKeyValue()
     * resolves the language - the fallback languages, then the language-independent default - while
     * getKeyValues() and getKeyValue() address the slot they are given and nothing else.
     *
     * The difference is deliberate and is pinned here because it is what a caller moving from one
     * reader to the other has to know: what the slot readers answer is what a save stores for that
     * slot, so a read that resolved a fallback would write another language's value into it.
     */
    public function testTheDocumentedGetterResolvesTheLanguageWhileTheSlotReadersDoNot(): void
    {
        $groupId = Classificationstore\GroupConfig::getByName('testgroup1')->getId();
        $keyId = $this->inputKeyId();

        $object = $this->createObject('one-slot');
        $object->getCsstore()->setActiveGroups([$groupId => true]);
        $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'language independent');
        $object->save();

        $store = Csstore::getById($object->getId(), ['force' => true])->getCsstore();

        $this->assertSame(
            'language independent',
            $store->getLocalizedKeyValue($groupId, $keyId, 'de'),
            'the documented getter falls back to the language-independent default'
        );
        $this->assertSame(
            [],
            $store->getKeyValues($groupId, $keyId, 'de'),
            'the slot reader answers the slot it was given, which holds nothing'
        );
        $this->assertNull($store->getKeyValue($groupId, $keyId, 'de'));

        // and the two agree about the slot that does hold the value
        $this->assertSame('language independent', $store->getLocalizedKeyValue($groupId, $keyId));
        $this->assertSame(['language independent'], $store->getKeyValues($groupId, $keyId));
        $this->assertSame('language independent', $store->getKeyValue($groupId, $keyId));
    }

    /**
     * Everything that answers a key definition without a statement, so that a rewritten definition
     * is the one the next read resolves.
     */
    private function clearDefinitionCaches(): void
    {
        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();
    }

    /**
     * A group of its own carrying one multi-value key, so that the key count the other tests of
     * this store assert stays what their own fixture made it.
     *
     * @param string[] $variants
     *
     * @return array{int, int} the group id and the key id
     */
    private function createMultiValueKey(array $variants, bool $list): array
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::MULTI_VALUE_GROUP);
        $group->save();

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::MULTI_VALUE_KEY);
        $keyConfig->setEnabled(true);
        $keyConfig->setType('multiValue');
        $keyConfig->setDefinition((string) json_encode($this->multiValueDefinition($variants, $list)));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId($keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(1);
        $relation->save();

        $this->clearDefinitionCaches();

        return [(int) $group->getId(), (int) $keyConfig->getId()];
    }

    private function createObject(string $key): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('multivalue-' . $key);
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    private function documentTable(): string
    {
        return self::DOCUMENT_TABLE_PREFIX . ClassDefinition::getByName('csstore')->getId();
    }

    /**
     * @param array<array<string, mixed>> $entries
     *
     * @return array<string, mixed>
     */
    private function entryOf(array $entries, string $variant): array
    {
        foreach ($entries as $entry) {
            if (($entry['var'] ?? 'default') === $variant) {
                return $entry;
            }
        }

        $this->fail('the document carries no entry for variant ' . $variant);
    }

    private function inputKeyId(): int
    {
        return $this->keyIdOf('input');
    }

    private function keyIdOf(string $name): int
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        return (int) Classificationstore\KeyConfig::getByName($name, $store->getId())->getId();
    }

    /**
     * @param string[] $values
     *
     * @return MultiValue[]
     */
    private function listEntries(array $values): array
    {
        $entries = [];
        foreach ($values as $position => $value) {
            $entries[] = new MultiValue($value, null, null, $position);
        }

        return $entries;
    }

    /**
     * @param string[] $variants
     */
    private function multiValueDefinition(array $variants, bool $list, bool $unqualified = true): MultiValueKey
    {
        $inner = new Input();
        $inner->setName(self::MULTI_VALUE_KEY);

        $definition = new MultiValueKey();
        $definition->setName(self::MULTI_VALUE_KEY);
        $definition->setVariants(array_map(static fn (string $name): array => ['name' => $name], $variants));
        $definition->setMultiValue($list);
        $definition->setUnqualifiedValue($unqualified);
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        return $definition;
    }

    /**
     * Rewrites the declaration of the fixture key, which is what narrowing and widening it is.
     *
     * @param string[] $variants
     */
    private function narrowMultiValueKey(array $variants, bool $list, bool $unqualified = true): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $keyConfig = Classificationstore\KeyConfig::getByName(self::MULTI_VALUE_KEY, $store->getId());
        $keyConfig->setDefinition((string) json_encode($this->multiValueDefinition($variants, $list, $unqualified)));
        $keyConfig->save();

        $this->clearDefinitionCaches();
    }

    /**
     * What the classification store field contributes to the object's search index, which is what an
     * indexing pass writes.
     */
    private function searchIndexData(Csstore $object): string
    {
        $fieldDefinition = ClassDefinition::getByName(self::CLASS_NAME)->getFieldDefinition(self::FIELDNAME);

        $this->assertInstanceOf(ClassificationstoreField::class, $fieldDefinition);

        return $fieldDefinition->getDataForSearchIndex($object);
    }

    /**
     * The stored entries of one key, as the group's document carries them.
     *
     * @return array<array<string, mixed>>
     */
    private function storedEntries(?int $objectId, int $groupId, int $keyId): array
    {
        $document = json_decode((string) $this->storedRow($objectId, $groupId)['values'], true);

        return $document['k'][$keyId] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedRow(?int $objectId, int $groupId): array
    {
        return (array) Db::get()->fetchAssociative(
            'SELECT `values`, `checksum`, `collectionId` FROM ' . $this->documentTable()
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$objectId, self::FIELDNAME, $groupId]
        );
    }

    private function textareaKeyId(): int
    {
        return $this->keyIdOf('textarea');
    }
}
