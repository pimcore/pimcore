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

use Exception;
use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tests\Support\Test\ModelTestCase;

abstract class AbstractClassificationStoreTest extends ModelTestCase
{
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
     * The storage engine the test store runs on. A subclass returning another name runs the very
     * same tests against that engine - the store is shared across the suite, so every test class
     * puts it on the engine it asks for rather than assuming the previous one left it there.
     */
    protected function getStoreEngine(): ?string
    {
        return null;
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
