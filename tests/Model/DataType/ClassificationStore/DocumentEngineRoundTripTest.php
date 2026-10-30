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
use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The two engines against each other: the same items are written through both and the two loaded
 * stores are compared with one another.
 *
 * Comparing a document-engine write against a document-engine read would round-trip cleanly even
 * if both halves were wrong, which is why the EAV engine is the reference here.
 *
 * @group model.datatype.classificationstore
 */
class DocumentEngineRoundTripTest extends AbstractClassificationStoreTest
{
    private const DOCUMENT_ENGINE = 'document';

    private const CLASS_NAME = 'csstore';

    private const FIRST_GROUP = 'testgroup1';

    private const SECOND_GROUP = 'testgroup2';

    /**
     * The key names the shipped test store carries, in the order
     * {@see AbstractClassificationStoreTest::configureStore()} creates them: the first three belong
     * to the first group, the rest to the second one.
     */
    private const FIRST_GROUP_KEYS = ['date', 'datetime', 'encryptedField'];

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->setStoreEngine(null);

        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * Every key type the shipped test store exercises comes back from the document engine as what
     * the EAV engine gives back for the same input.
     */
    public function testEveryKeyTypeLoadsBackWhatTheEavEngineLoadsBack(): void
    {
        $values = $this->buildValues();

        $this->assertCount(
            AbstractClassificationStoreTest::$configCount,
            $values,
            'every key the store configures is written'
        );

        $eavValues = $this->writeAndReadBack('round-trip-eav', $values, null);
        $documentValues = $this->writeAndReadBack('round-trip-document', $values, self::DOCUMENT_ENGINE);

        foreach (array_keys($values) as $keyName) {
            $this->assertEquals(
                $eavValues[$keyName],
                $documentValues[$keyName],
                'the document engine returns another value than the EAV engine for ' . $keyName
            );
        }
    }

    /**
     * The value every key of the test store is written with.
     *
     * @return array<string, mixed> key name => value
     */
    private function buildValues(): array
    {
        $unit = Unit::getByAbbreviation('mm');
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation('mm');
            $unit->save();
        }

        return [
            'date' => new Carbon('2026-10-29 00:00:00'),
            'datetime' => new Carbon('2026-10-29 10:11:12'),
            'encryptedField' => new EncryptedField(new Input(), 'abc'),
            'input' => 'abc',
            'rgbaColor' => new RgbaColor(1, 2, 3, 4),
            'select' => 'B',
            'time' => '12:30',
            'numeric' => 12.57,
            'booleanSelect' => true,
            'user' => $this->getUserId(),
            'textarea' => "line1\nline2",
            'wysiwyg' => 'line1<br />line2',
            'checkbox' => true,
            'slider' => 47,
            'table' => [['A', 'B'], ['C', 'D']],
            'country' => 'AT',
            'language' => 'fr',
            'multiselect' => ['A', 'D'],
            'countrymultiselect' => ['AT', 'DE'],
            'languagemultiselect' => ['AT', 'DE'],
            'quantityValue' => new QuantityValue(123, $unit->getId()),
            'inputQuantityValue' => new InputQuantityValue('abc', $unit->getId()),
        ];
    }

    /**
     * Writes every value through the given engine and reads the stored object back.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed> key name => loaded value
     */
    private function writeAndReadBack(string $objectKey, array $values, ?string $engine): array
    {
        $store = $this->setStoreEngine($engine);

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($objectKey);
        $object->setPublished(true);

        $classificationStore = $object->getCsstore();
        foreach ($values as $keyName => $value) {
            $classificationStore->setLocalizedKeyValue(
                $this->getGroupId($keyName),
                $this->getKeyId($keyName, $store),
                $value
            );
        }
        $object->save();

        $reloaded = Csstore::getById($object->getId(), ['force' => true])->getCsstore();

        $loadedValues = [];
        foreach (array_keys($values) as $keyName) {
            $loadedValues[$keyName] = $reloaded->getLocalizedKeyValue(
                $this->getGroupId($keyName),
                $this->getKeyId($keyName, $store)
            );
        }

        return $loadedValues;
    }

    private function setStoreEngine(?string $engine): Classificationstore\StoreConfig
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $store->setEngine($engine);
        $store->save();

        $this->createValueTables($store, self::CLASS_NAME);

        return $store;
    }

    private function getGroupId(string $keyName): int
    {
        $groupName = in_array($keyName, self::FIRST_GROUP_KEYS, true) ? self::FIRST_GROUP : self::SECOND_GROUP;

        return Classificationstore\GroupConfig::getByName($groupName)->getId();
    }

    private function getKeyId(string $keyName, Classificationstore\StoreConfig $store): int
    {
        return Classificationstore\KeyConfig::getByName($keyName, $store->getId())->getId();
    }

    private function getUserId(): int
    {
        $userListing = new User\Listing();
        $userListing->setLimit(1);
        $users = $userListing->load();

        if ($users) {
            return $users[0]->getId();
        }

        $user = new User();
        $user->setName('testuser');
        $user->save();

        return $user->getId();
    }
}
