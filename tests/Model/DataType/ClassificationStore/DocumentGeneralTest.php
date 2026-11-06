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
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\NumericRange;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tests\Support\Helper\DataType\CustomValueObject;
use Pimcore\Tests\Support\Helper\DataType\MixedNormalizingField;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * GeneralTest against a store on the document engine: the values of every key type, their languages and their fallbacks
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentGeneralTest extends GeneralTest
{
    private const STORE_NAME = 'teststore';

    private const FIELDNAME = 'csstore';

    private const KEY_PREFIX = 'cs-structured-';

    private const CLASS_NAME = 'csstore';

    private const DOCUMENT_TABLE = 'object_classificationstore_doc_';

    private const ENCRYPTED_GROUP_NAME = 'testgroupEncryptedGeopoint';

    private const ENCRYPTED_KEY_NAME = 'encryptedGeopoint';

    private const MIXED_GROUP_NAME = 'testgroupEncryptedMixed';

    private const MIXED_KEY_NAME = 'encryptedMixedNormalizing';

    private const DEFAULT_LANGUAGE = 'default';

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
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $group = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $key = $store ? Classificationstore\KeyConfig::getByName('qValue', $store->getId()) : null;

        $key?->delete();
        $group?->delete();

        parent::tearDown();
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

    private function createEncryptedGeopointKey(
        Classificationstore\StoreConfig $store,
        Classificationstore\GroupConfig $group
    ): Classificationstore\KeyConfig {
        $definition = new EncryptedFieldDefinition();
        $definition->setName(self::ENCRYPTED_KEY_NAME);
        $definition->setDelegateDatatype('geopoint');
        $definition->setDelegate(new Geopoint());

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::ENCRYPTED_KEY_NAME);
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
        $hotspotimage->setCrop(['cropWidth' => 10.0, 'cropHeight' => 20.0, 'cropTop' => 1.0, 'cropLeft' => 2.0]);

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
}
