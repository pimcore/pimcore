<?php

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tests\Test\ModelTestCase;

abstract class AbstractClassificationStoreTest extends ModelTestCase
{
    /**
     * @inheritDoc
     */
    protected function setUpTestClasses()
    {
        $class = ClassDefinition::getByName('csstore');

        if (!$class) {
            $store = Classificationstore\StoreConfig::getByName('teststore');
            if (!$store) {
                $store = new Classificationstore\StoreConfig();
                $store->setName('teststore');
                $store->save();
                $this->configureStore($store);
            }

            $class = $this->tester->setupPimcoreClass_Csstore([
                'storeId' => $store->getId(),
            ]);
        }

        return $class;
    }

    /**
     * @param Classificationstore\StoreConfig $store
     */
    protected function configureStore(Classificationstore\StoreConfig $store)
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

        $keyNames = ['key1', 'key2', 'key3', 'key4', 'key5', 'key6'];
        for ($i = 0; $i < count($keyNames); $i++) {
            $keyName = $keyNames[$i];
            $keyConfig = Classificationstore\KeyConfig::getByName($keyName, $i < 3 ? $group1->getId() : $group2->getId());
            if (!$keyConfig) {
                $keyConfig = new Classificationstore\KeyConfig();
                $keyConfig->setStoreId($store->getId());
                $keyConfig->setName($keyName);
                $keyConfig->setDescription('keyDesc' . $keyName . 'Desc');
                $keyConfig->setEnabled(true);
                $keyConfig->setType('input');

                if ($i < 3) {
                    $definition = new ClassDefinition\Data\Input();
                } else {
                    $definition = new ClassDefinition\Data\Select();
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
