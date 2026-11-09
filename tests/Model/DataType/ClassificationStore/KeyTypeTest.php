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
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\EncryptedField as EncryptedFieldValue;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Tests\Support\Helper\DataType\CustomValueObject;
use Pimcore\Tests\Support\Helper\DataType\MixedNormalizingField;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Which field types a key of an EAV store may be defined with, and what happens to a value of a
 * type it cannot hold.
 *
 * The two checks are deliberately held to the same type here: `geopoint` is refused when the key is
 * defined, and refused again when a value reaches the engine behind a key row that was written
 * without passing the definition - an upgraded installation, or a direct SQL edit. A fix that
 * satisfied one of them alone would leave the other failing.
 *
 * @group model.datatype.classificationstore
 */
class KeyTypeTest extends AbstractClassificationStoreTest
{
    private const STORE_NAME = 'teststore';

    private const GROUP_NAME = 'testgroup2';

    private const CLASS_NAME = 'csstore';

    private const FIELDNAME = 'csstore';

    private const KEY_PREFIX = 'cs-key-type-';

    private const GEOPOINT_TYPE = 'geopoint';

    private const INPUT_TYPE = 'input';

    private const ENCRYPTED_FIELD_TYPE = 'encryptedField';

    /**
     * The test-only field type that declares `mixed` and returns an array.
     * {@see MixedNormalizingField}, registered in the test container's class definition data map.
     */
    private const MIXED_NORMALIZING_TYPE = 'mixedNormalizingObject';

    private const DATA_TABLE = 'object_classificationstore_data_';

    private const KEYS_TABLE = 'classificationstore_keys';

    private const DEFAULT_LANGUAGE = 'default';

    /**
     * @var Classificationstore\KeyConfig[]
     */
    private array $createdKeys = [];

    /**
     * @var array<array{string, callable}>
     */
    private array $listeners = [];

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->listeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->listeners = [];

        foreach ($this->createdKeys as $key) {
            Db::get()->delete(self::KEYS_TABLE, ['id' => $key->getId()]);
        }
        $this->createdKeys = [];

        $this->clearDefinitionCaches();

        TestHelper::cleanUp();

        parent::tearDown();
    }

    public function testDefiningAStructuredKeyOnAnEavStoreIsRefusedAndWritesNothing(): void
    {
        $store = $this->getStore();

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::KEY_PREFIX . self::GEOPOINT_TYPE);
        $keyConfig->setType(self::GEOPOINT_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition(new Geopoint()));

        try {
            $keyConfig->save();
            $this->fail('a geopoint key on an EAV store has to be refused');
        } catch (UnsupportedEngineFeatureException $exception) {
            $this->assertStringContainsString(self::GEOPOINT_TYPE, $exception->getMessage());
            $this->assertStringContainsString('"eav"', $exception->getMessage());
            $this->assertStringContainsString('store ' . $store->getId(), $exception->getMessage());
        }

        $this->assertNull($keyConfig->getId(), 'no row was inserted');
        $this->assertNull($keyConfig->getIdentifier(), 'the identifier of a refused key is not consumed');
        $this->assertSame(
            0,
            $this->countKeyRows(self::KEY_PREFIX . self::GEOPOINT_TYPE, $store->getId()),
            'no key row for the refused name'
        );
    }

    public function testChangingTheTypeOfAStoredKeyToAStructuredOneLeavesTheStoredTypeAlone(): void
    {
        $keyConfig = $this->createInputKey('switched');

        $keyConfig->setType(self::GEOPOINT_TYPE);
        $keyConfig->setDefinition($this->encodeDefinition(new Geopoint()));

        $this->expectException(UnsupportedEngineFeatureException::class);

        try {
            $keyConfig->save();
        } finally {
            $this->assertSame(
                self::INPUT_TYPE,
                $this->readKeyType((int) $keyConfig->getId()),
                'the stored type column is unchanged'
            );
        }
    }

    /**
     * The holdability check follows an encryptedField into its delegate instead of stopping at the
     * marshaller the encryptedField itself has: that marshaller encrypts what the delegate's
     * marshaller packed, so a delegate without one leaves it nothing to encrypt and stores null.
     */
    public function testAnEncryptedFieldWithAStructuredDelegateIsRefusedOnAnEavStore(): void
    {
        $store = $this->getStore();

        $definition = new EncryptedField();
        $definition->setDelegateDatatype(self::GEOPOINT_TYPE);
        $definition->setDelegate(new Geopoint());

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::KEY_PREFIX . 'encryptedGeopoint');
        $keyConfig->setType(self::ENCRYPTED_FIELD_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition($definition));

        $this->expectException(UnsupportedEngineFeatureException::class);
        $keyConfig->save();
    }

    public function testAnEncryptedFieldWithAScalarDelegateIsStillDefinableOnAnEavStore(): void
    {
        $store = $this->getStore();

        $definition = new EncryptedField();
        $definition->setDelegateDatatype(self::INPUT_TYPE);
        $definition->setDelegate(new Input());

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::KEY_PREFIX . 'encryptedInput');
        $keyConfig->setType(self::ENCRYPTED_FIELD_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition($definition));
        $keyConfig->save();
        $this->createdKeys[] = $keyConfig;

        $this->assertNotNull($keyConfig->getId());
    }

    /**
     * The engine never writes a value it cannot represent, so a key row that was written without
     * passing the definition check fails loudly instead of stringifying an array into the column.
     */
    public function testAValueOfATypeTheEngineCannotHoldIsRefusedInsteadOfWritten(): void
    {
        $store = $this->getStore();
        $group = $this->getGroup();
        $keyConfig = $this->createInputKey('smuggled');

        // what an upgraded installation or a direct SQL edit leaves behind: a key row of a type the
        // definition check would refuse
        Db::get()->update(
            self::KEYS_TABLE,
            ['type' => self::GEOPOINT_TYPE, 'definition' => $this->encodeDefinition(new Geopoint())],
            ['id' => $keyConfig->getId()]
        );
        $this->clearDefinitionCaches();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(99);
        $relation->save();

        $object = $this->createObject('smuggled');
        $csField = $object->getCsstore();
        $csField->setActiveGroups([$group->getId() => true]);
        $csField->setLocalizedKeyValue(
            $group->getId(),
            (int) $keyConfig->getId(),
            new GeoCoordinates(48.2, 16.37)
        );

        try {
            $object->save();
            $this->fail('the engine has to refuse a value it cannot represent');
        } catch (UnsupportedEngineFeatureException $exception) {
            $this->assertStringContainsString(self::GEOPOINT_TYPE, $exception->getMessage());
        }

        $this->assertSame(
            0,
            $this->countDataRows((int) $object->getId(), $group->getId(), (int) $keyConfig->getId()),
            'no value row was written for the refused key'
        );
    }

    /**
     * The declaration is not the value, and a refusal about storage has to be about the value.
     *
     * `mixedNormalizingObject` declares `mixed` and returns an array, so an `encryptedField`
     * delegating to it passes the definition check - a declaration is all that check can have. The
     * encryptedField marshaller then has nothing to pack into the two columns, hands both of them
     * back as `null`
     * ({@see \Pimcore\DataObject\ClassificationstoreDataMarshaller\EncryptedField::marshal()}), and
     * a check on what is about to be written sees two nulls that look like an empty value. So the
     * save has to be refused before the marshaller rather than after it, or it succeeds having
     * stored nothing.
     */
    public function testAnEncryptedFieldWhoseDelegateReturnsAStructureIsRefusedWhenAValueIsWritten(): void
    {
        $store = $this->getStore();
        $group = $this->getGroup();

        $definition = new EncryptedField();
        $definition->setDelegateDatatype(self::MIXED_NORMALIZING_TYPE);
        $definition->setDelegate(new MixedNormalizingField());

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::KEY_PREFIX . 'encryptedMixed');
        $keyConfig->setType(self::ENCRYPTED_FIELD_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition($definition));

        // the definition check accepts it: the delegate declares a scalar-compatible normalized type
        $keyConfig->save();
        $this->createdKeys[] = $keyConfig;

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(97);
        $relation->save();

        $object = $this->createObject('encrypted-mixed');
        $csField = $object->getCsstore();
        $csField->setActiveGroups([$group->getId() => true]);
        $csField->setLocalizedKeyValue(
            $group->getId(),
            (int) $keyConfig->getId(),
            new EncryptedFieldValue(new MixedNormalizingField(), new CustomValueObject('secret'))
        );

        try {
            $object->save();
            $this->fail('the engine has to refuse a value its marshaller cannot pack');
        } catch (UnsupportedEngineFeatureException $exception) {
            $this->assertStringContainsString(self::ENCRYPTED_FIELD_TYPE, $exception->getMessage());
        }

        $this->assertSame(
            0,
            $this->countDataRows((int) $object->getId(), $group->getId(), (int) $keyConfig->getId()),
            'no value row was written, and no row of two nulls either'
        );
    }

    /**
     * `calculatedValue` is excluded from the set this release unlocks, and that exclusion does not
     * reach back: core has purpose-written support for such a key in a classification store, and it
     * is neither refused when the key is defined nor when the object is saved.
     */
    public function testACalculatedValueKeyStillComputesOnReadAndReachesTheEditmodePayload(): void
    {
        $store = $this->getStore();
        $group = $this->getGroup();

        $definition = new CalculatedValue();
        $definition->setCalculatorType(CalculatedValue::CALCULATOR_TYPE_EXPRESSION);
        $definition->setCalculatorExpression('"computed"');

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::KEY_PREFIX . 'calculated');
        $keyConfig->setType('calculatedValue');
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition($definition));
        $keyConfig->save();
        $this->createdKeys[] = $keyConfig;

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId((int) $keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(98);
        $relation->save();

        $object = $this->createObject('calculated');
        $csField = $object->getCsstore();
        $csField->setActiveGroups([$group->getId() => true]);
        $object->save();

        $this->assertSame(
            'computed',
            $csField->getLocalizedKeyValue($group->getId(), (int) $keyConfig->getId()),
            'a calculatedValue key still computes on read'
        );

        $payload = $this->getCsstoreFieldDefinition()->getDataForEditmode($csField, $object);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey(
            (int) $keyConfig->getId(),
            $payload['data'][self::DEFAULT_LANGUAGE][$group->getId()] ?? [],
            'a calculatedValue key still reaches the editmode payload'
        );
    }

    /**
     * The check runs again after the pre-save event, because the event can change what the save is
     * about to write.
     *
     * {@see Classificationstore\KeyConfig::save()} dispatches `KEY_CONFIG_PRE_ADD` with this very
     * object, so a listener is free to assign a different type, store or encrypted delegate between
     * the first check and the DAO. Checked only before the event, an accepted `input` key would
     * reach {@see Classificationstore\KeyConfig\Dao::save()} as the `geopoint` the listener made of
     * it, and an EAV store would hold a key whose values it cannot store - the configuration the
     * definition-time refusal exists to keep out.
     */
    public function testAListenerChangingTheTypeOnTheAddEventIsRefusedAndWritesNothing(): void
    {
        $store = $this->getStore();
        $name = self::KEY_PREFIX . 'event-add';

        $this->makeTheListenerAssignGeopoint(DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_ADD, $name);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName($name);
        $keyConfig->setType(self::INPUT_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition(new Input()));

        try {
            $keyConfig->save();
            $this->fail('a type an EAV store cannot hold has to be refused however the save came by it');
        } catch (UnsupportedEngineFeatureException $exception) {
            $this->assertStringContainsString(self::GEOPOINT_TYPE, $exception->getMessage());
            $this->assertStringContainsString('"eav"', $exception->getMessage());
        }

        $this->assertSame(
            0,
            $this->countKeyRows($name, $store->getId()),
            'the row the listener made unsupported was not inserted'
        );
    }

    /**
     * The same on an update: the stored key keeps the type it was saved with, and the listener's
     * change reaches no column.
     */
    public function testAListenerChangingTheTypeOnTheUpdateEventIsRefusedAndLeavesTheStoredRowAlone(): void
    {
        $keyConfig = $this->createInputKey('event-update');
        $keyId = (int) $keyConfig->getId();

        $this->makeTheListenerAssignGeopoint(
            DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_UPDATE,
            $keyConfig->getName()
        );

        $keyConfig->setEnabled(false);

        try {
            $keyConfig->save();
            $this->fail('an update the listener made unsupported has to be refused');
        } catch (UnsupportedEngineFeatureException $exception) {
            $this->assertStringContainsString(self::GEOPOINT_TYPE, $exception->getMessage());
        }

        $this->assertSame(
            self::INPUT_TYPE,
            $this->readKeyType($keyId),
            'the stored type is the one the key was saved with'
        );
    }

    /**
     * Registers a pre-save listener that turns the key of this name into a `geopoint` - the type
     * {@see self::testDefiningAStructuredKeyOnAnEavStoreIsRefusedAndWritesNothing()} holds the
     * definition-time refusal to. Keys of other names are left alone, so the listener cannot
     * disturb the fixtures the rest of the suite saves.
     */
    private function makeTheListenerAssignGeopoint(string $eventName, string $name): void
    {
        $listener = function (KeyConfigEvent $event) use ($name): void {
            $keyConfig = $event->getKeyConfig();

            if ($keyConfig->getName() !== $name) {
                return;
            }

            $keyConfig->setType(self::GEOPOINT_TYPE);
            $keyConfig->setDefinition($this->encodeDefinition(new Geopoint()));
        };

        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->listeners[] = [$eventName, $listener];
    }

    private function createInputKey(string $suffix): Classificationstore\KeyConfig
    {
        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($this->getStore()->getId());
        $keyConfig->setName(self::KEY_PREFIX . $suffix);
        $keyConfig->setType(self::INPUT_TYPE);
        $keyConfig->setEnabled(true);
        $keyConfig->setDefinition($this->encodeDefinition(new Input()));
        $keyConfig->save();

        $this->createdKeys[] = $keyConfig;

        return $keyConfig;
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

    private function encodeDefinition(ClassDefinition\Data $definition): string
    {
        $definition->setName('keyDefinition');

        return (string) json_encode($definition);
    }

    private function clearDefinitionCaches(): void
    {
        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();
        RuntimeCache::clear();
        Cache::clearAll();
    }

    private function countKeyRows(string $name, int $storeId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT count(*) FROM ' . self::KEYS_TABLE . ' WHERE name = ? AND storeId = ?',
            [$name, $storeId]
        );
    }

    private function readKeyType(int $keyId): string
    {
        return (string) Db::get()->fetchOne(
            'SELECT type FROM ' . self::KEYS_TABLE . ' WHERE id = ?',
            [$keyId]
        );
    }

    private function countDataRows(int $objectId, int $groupId, int $keyId): int
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertNotNull($class);

        return (int) Db::get()->fetchOne(
            'SELECT count(*) FROM ' . self::DATA_TABLE . $class->getId()
            . ' WHERE id = ? AND fieldname = ? AND groupId = ? AND keyId = ?',
            [$objectId, self::FIELDNAME, $groupId, $keyId]
        );
    }

    private function getStore(): Classificationstore\StoreConfig
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertNotNull($store);

        return $store;
    }

    private function getGroup(): Classificationstore\GroupConfig
    {
        $group = Classificationstore\GroupConfig::getByName(self::GROUP_NAME, $this->getStore()->getId());
        $this->assertNotNull($group);

        return $group;
    }

    private function getCsstoreFieldDefinition(): ClassificationstoreField
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertNotNull($class);

        $fieldDefinition = $class->getFieldDefinition(self::FIELDNAME);
        $this->assertInstanceOf(ClassificationstoreField::class, $fieldDefinition);

        return $fieldDefinition;
    }
}
