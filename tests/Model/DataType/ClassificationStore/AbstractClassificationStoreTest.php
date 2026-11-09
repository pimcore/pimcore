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

use DateTimeInterface;
use Exception;
use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Test\ModelTestCase;

abstract class AbstractClassificationStoreTest extends ModelTestCase
{
    /**
     * The thirteen field types the document engine unlocks: accepted, held by no value column of an
     * EAV row, and not a relation type.
     *
     * @var string[]
     */
    protected const STRUCTURED_KEY_NAMES = [
        'consent', 'dateRange', 'externalImage', 'geobounds', 'geopoint', 'geopolygon', 'geopolyline',
        'hotspotimage', 'imageGallery', 'link', 'numericRange', 'structuredTable', 'video',
    ];

    protected const STRUCTURED_GROUP_NAME = 'testgroupStructured';

    public static int $configCount = 0;

    protected function configureStoreWithQuantityValueField(Classificationstore\StoreConfig $store): void
    {
        // create group
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName('testgroupQvalue');
        $group->save();

        //create key
        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName('qValue');
        $keyConfig->setDescription('Quantity Value Field');
        $keyConfig->setEnabled(true);
        $keyConfig->setType('quantityValue');

        //QuantityValue definition
        $definition = new ClassDefinition\Data\QuantityValue();
        $definition->setName('qValue');
        $definition = json_encode($definition);

        $keyConfig->setDefinition($definition); // The definition is used in object editor to render fields
        $keyConfig->save();

        //create key group relation
        $keygroupconfig = new Classificationstore\KeyGroupRelation();
        $keygroupconfig->setKeyId($keyConfig->getId());
        $keygroupconfig->setGroupId($group->getId());
        $keygroupconfig->setSorter(1);
        $keygroupconfig->save();
    }

    /**
     * Adds a group holding one key of every structured type to the store, which only an engine
     * offering {@see Classificationstore\Engine\EngineFeature::StructuredValues} accepts - defining
     * them on an EAV store is refused, which is why they are not part of the shared key set of
     * {@see self::configureStore()} and are created and dropped by the test that needs them.
     *
     * @throws Exception
     */
    protected function configureStoreWithStructuredFields(
        Classificationstore\StoreConfig $store
    ): Classificationstore\GroupConfig {
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::STRUCTURED_GROUP_NAME);
        $group->save();

        foreach (self::STRUCTURED_KEY_NAMES as $sorter => $keyName) {
            $keyConfig = new Classificationstore\KeyConfig();
            $keyConfig->setStoreId($store->getId());
            $keyConfig->setName($keyName);
            $keyConfig->setDescription('keyDesc' . $keyName . 'Desc');
            $keyConfig->setEnabled(true);
            $keyConfig->setType($keyName);
            $keyConfig->setDefinition($this->encodeKeyDefinition($keyName));
            $keyConfig->save();

            $relation = new Classificationstore\KeyGroupRelation();
            $relation->setKeyId($keyConfig->getId());
            $relation->setGroupId($group->getId());
            $relation->setSorter($sorter);
            $relation->save();
        }

        return $group;
    }

    /**
     * Removes the group of {@see self::configureStoreWithStructuredFields()} and its keys again. The
     * store is shared across the suite, and a key left behind would change the key count
     * {@see GeneralTest::testBasics()} expects of it.
     */
    protected function dropStructuredFields(Classificationstore\StoreConfig $store): void
    {
        foreach (self::STRUCTURED_KEY_NAMES as $keyName) {
            Classificationstore\KeyConfig::getByName($keyName, $store->getId(), true)?->delete();
        }

        Classificationstore\GroupConfig::getByName(self::STRUCTURED_GROUP_NAME, $store->getId())?->delete();
    }

    /**
     * The field definition of a structured key, serialized the way a key config carries it.
     *
     * @throws Exception
     */
    private function encodeKeyDefinition(string $keyName): string
    {
        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');
        if (!$loader->supports($keyName)) {
            throw new Exception($keyName . ' not supported');
        }

        /** @var ClassDefinition\Data $definition */
        $definition = $loader->build($keyName);
        $definition->setName($keyName);

        return (string) json_encode($definition);
    }

    /**
     * The storage engine the test store runs on. A subclass returning another name runs the very
     * same tests against that engine - the store is shared across the suite, so every test class
     * puts it on the engine it asks for rather than assuming the previous one left it there.
     */
    protected function getStoreEngine(): ?string
    {
        return null;
    }

    /**
     * Two stored values hold the same thing.
     *
     * A value that carries its own setters carries a back-reference to the store holding it, so
     * that a change made through one of those setters marks the store dirty -
     * {@see Classificationstore::setLocalizedKeyValue()} attaches it and
     * {@see \Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait::markMeDirty()} reads it. That
     * reference says where the value lives, not what it holds, and a value read back from storage
     * lives in a different store object than the one it is compared against - the other engine's
     * store, or the same store reloaded after a drain. `assertEquals()` on the value itself
     * compares objects member by member, all the way down
     * ({@see \SebastianBergmann\Comparator\ObjectComparator::assertEquals()} casts both sides to
     * arrays), so it would walk into both stores and both data objects behind them and fail on a
     * value that came back perfectly.
     *
     * So the value is compared by its own members, which is how the house asserts such a value
     * everywhere else: {@see \Pimcore\Tests\Support\Helper\DataType\TestDataHelper::assertQuantityValue()}
     * reads `getValue()` and the unit rather than comparing the object. This does the same thing
     * for any value, however deeply nested, and keeps each class name in the comparison so that a
     * value coming back as the wrong type still fails. What a value reports as its owner is
     * asserted where that is the subject, not here.
     */
    protected function assertSameStoredValue(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertEquals(
            $this->describeValue($expected),
            $this->describeValue($actual),
            $message
        );
    }

    /**
     * A value as the members it is made of, without the owner bookkeeping of
     * {@see OwnerAwareFieldInterface}.
     *
     * An element is described by its type and id: it is referenced by the value rather than part of
     * it, and how much of it happens to be loaded is nothing the storage decides.
     */
    private function describeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 12) {
            return '*too deep*';
        }

        if (is_array($value)) {
            return array_map(fn (mixed $member): mixed => $this->describeValue($member, $depth + 1), $value);
        }

        if ($value instanceof ElementInterface) {
            return ['__element' => $value->getType() . ':' . $value->getId()];
        }

        if (!is_object($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return ['__class' => $value::class, 'at' => $value->format('U.u')];
        }

        $described = ['__class' => $value::class];

        foreach ((array) $value as $property => $member) {
            if ($value instanceof OwnerAwareFieldInterface
                && in_array($this->getPropertyName((string) $property), ['_owner', '_fieldname', '_language'], true)
            ) {
                continue;
            }

            $described[(string) $property] = $this->describeValue($member, $depth + 1);
        }

        return $described;
    }

    /**
     * The name of a property as the array cast of its object keys it: a protected one as
     * `"\0*\0<name>"` and a private one as `"\0<declaring class>\0<name>"`.
     */
    private function getPropertyName(string $key): string
    {
        $lastNul = strrpos($key, "\0");

        return $lastNul === false ? $key : substr($key, $lastNul + 1);
    }

    protected function setUpTestClasses(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        if (!$store) {
            $store = new Classificationstore\StoreConfig();
            $store->setName('teststore');
            $store->setEngine($this->getStoreEngine());
            $store->save();
            $this->configureStore($store);
        } elseif ($store->getEngine() !== $this->getStoreEngine()) {
            $store->setEngine($this->getStoreEngine());
            $store->save();
        }

        if (!ClassDefinition::getByName('csstore')) {
            $this->tester->setupPimcoreClass_Csstore([
                'storeId' => $store->getId(),
            ]);

            return;
        }

        $this->createValueTables($store, 'csstore');
    }

    /**
     * The value tables of the engine the store is on now. Creating them is idempotent, and the
     * class definition was saved while the store was on whichever engine the previous test class
     * asked for, so its tables cannot be assumed to be there.
     */
    protected function createValueTables(Classificationstore\StoreConfig $store, string $className): void
    {
        $class = ClassDefinition::getByName($className);
        if (!$class) {
            return;
        }

        Pimcore::getContainer()->get(Classificationstore\Engine\EngineResolver::class)
            ->resolveForStore($store->getId())
            ->createUpdateTables($class, $store->getId());
    }

    protected function configureStore(Classificationstore\StoreConfig $store): void
    {
        $group1 = Classificationstore\GroupConfig::getByName('testgroup1');
        if (!$group1) {
            $group1 = new Classificationstore\GroupConfig();
            $group1->setStoreId($store->getId());
            $group1->setName('testgroup1');
            $group1->save();
        }

        $group2 = Classificationstore\GroupConfig::getByName('testgroup2');
        if (!$group2) {
            $group2 = new Classificationstore\GroupConfig();
            $group2->setStoreId($store->getId());
            $group2->setName('testgroup2');
            $group2->save();
        }

        $keyNames = ['date', 'datetime', 'encryptedField', 'input', 'rgbaColor', 'select', 'time', 'numeric', 'booleanSelect', 'user', 'textarea', 'wysiwyg', 'checkbox', 'slider',
            'table', 'country', 'language', 'multiselect', 'countrymultiselect', 'languagemultiselect', 'quantityValue', 'inputQuantityValue', 'image', 'encryptedImage', ];

        self::$configCount = count($keyNames);

        for ($i = 0; $i < count($keyNames); $i++) {
            $keyName = $keyNames[$i];
            $keyConfig = Classificationstore\KeyConfig::getByName($keyName, $i < 3 ? $group1->getId() : $group2->getId());
            if (!$keyConfig) {
                $keyConfig = new Classificationstore\KeyConfig();
                $keyConfig->setStoreId($store->getId());
                $keyConfig->setName($keyName);
                $keyConfig->setDescription('keyDesc' . $keyName . 'Desc');
                $keyConfig->setEnabled(true);
                $keyConfig->setType($keyName);

                switch ($keyName) {
                    case 'booleanSelect':
                        $definition = new ClassDefinition\Data\BooleanSelect();

                        break;
                    case 'checkbox':
                        $definition = new ClassDefinition\Data\Checkbox();

                        break;
                    case 'country':
                        $definition = new ClassDefinition\Data\Country();

                        break;
                    case 'countrymultiselect':
                        $definition = new ClassDefinition\Data\Countrymultiselect();

                        break;
                    case 'date':
                        $definition = new ClassDefinition\Data\Date();

                        break;
                    case 'dateRange':
                        $definition = new ClassDefinition\Data\DateRange();

                        break;
                    case 'datetime':
                        $definition = new ClassDefinition\Data\Datetime();

                        break;
                    case 'encryptedField':
                        $delegate = new ClassDefinition\Data\Input();
                        $definition = new ClassDefinition\Data\EncryptedField();
                        $definition->setDelegateDatatype('input');
                        $definition->setDelegate($delegate);

                        break;
                    case 'encryptedImage':
                        // the key name is only a label, the field type of the key is the encrypted field
                        $delegate = new ClassDefinition\Data\Image();
                        $definition = new ClassDefinition\Data\EncryptedField();
                        $definition->setDelegateDatatype('image');
                        $definition->setDelegate($delegate);
                        $keyConfig->setType('encryptedField');

                        break;
                    case 'image':
                        $definition = new ClassDefinition\Data\Image();

                        break;
                    case 'input':
                        $definition = new ClassDefinition\Data\Input();

                        break;
                    case 'inputQuantityValue':
                        $definition = new ClassDefinition\Data\InputQuantityValue();

                        break;
                    case 'language':
                        $definition = new ClassDefinition\Data\Language();

                        break;
                    case 'languagemultiselect':
                        $definition = new ClassDefinition\Data\Languagemultiselect();

                        break;
                    case 'multiselect':
                        $definition = new ClassDefinition\Data\Multiselect();

                        break;
                    case 'numeric':
                        $definition = new ClassDefinition\Data\Numeric();

                        break;
                    case 'rgbaColor':
                        $definition = new ClassDefinition\Data\RgbaColor();

                        break;
                    case 'select':
                        $definition = new ClassDefinition\Data\Select();

                        break;
                    case 'slider':
                        $definition = new ClassDefinition\Data\Slider();

                        break;
                    case 'table':
                        $definition = new ClassDefinition\Data\Table();

                        break;
                    case 'textarea':
                        $definition = new ClassDefinition\Data\Textarea();

                        break;
                    case 'time':
                        $definition = new ClassDefinition\Data\Time();

                        break;
                    case 'user':
                        $definition = new ClassDefinition\Data\User();

                        break;
                    case 'quantityValue':
                        $definition = new ClassDefinition\Data\QuantityValue();

                        break;
                    case 'wysiwyg':
                        $definition = new ClassDefinition\Data\Wysiwyg();

                        break;

                    default:
                        throw new Exception($keyName . ' not supported');
                }

                $definition->setName($keyName);
                $definition = json_encode($definition);

                $keyConfig->setDefinition($definition); // The definition is used in object editor to render fields
                $keyConfig->save();
            }

            $keygroupconfig = new Classificationstore\KeyGroupRelation();
            $keygroupconfig->setKeyId($keyConfig->getId());
            $keygroupconfig->setGroupId($i < 3 ? $group1->getId() : $group2->getId());
            $keygroupconfig->setSorter($i);
            $keygroupconfig->save();
        }
    }
}
