<?php
//require_once 'PHPUnit/Framework.php';



class TestSuite_Classificationstore_AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new TestSuite_Classificationstore_AllTests('Classificationstore');

        $tests = ['TestSuite_Classificationstore_GeneralTest'];

        $success = shuffle($tests);
        print("Created the following execution order:\n");

        foreach ($tests as $test) {
            print("    - " . $test . "\n");
            $suite->addTestSuite($test);
        }

        return $suite;
    }


    /**
     * @param $store \Pimcore\Model\Object\Classificationstore\StoreConfig
     */
    protected function configureStore($store)
    {
        $group1= \Pimcore\Model\Object\Classificationstore\GroupConfig::getByName("testgroup1");
        if (!$group1) {
            $group1 = new \Pimcore\Model\Object\Classificationstore\GroupConfig();
            $group1->setStoreId($store->getId());
            $group1->setName("testgroup1");
            $group1->save();
        }

        $group2= \Pimcore\Model\Object\Classificationstore\GroupConfig::getByName("testgroup2");
        if (!$group2) {
            $group2 = new \Pimcore\Model\Object\Classificationstore\GroupConfig();
            $group2->setStoreId($store->getId());
            $group2->setName("testgroup2");
            $group2->save();
        }



        $keyNames = ["key1", "key2", "key3", "key4", "key5", "key6"];

        for ($i = 0; $i < count($keyNames); $i++) {
            $keyName = $keyNames[$i];
            $keyConfig = \Pimcore\Model\Object\Classificationstore\KeyConfig::getByName($keyName, $i < 3 ? $group1->getId() : $group2->getId());
            if (!$keyConfig) {
                $keyConfig = new \Pimcore\Model\Object\Classificationstore\KeyConfig();
                $keyConfig->setStoreId($store->getId());
                $keyConfig->setName($keyName);
                $keyConfig->setDescription("keyDesc" . $keyName . "Desc");
                $keyConfig->setEnabled(true);
                $keyConfig->setType("input");
                if ($i < 3) {
                    $definition = new \Pimcore\Model\Object\ClassDefinition\Data\Input();
                } else {
                    $definition = new \Pimcore\Model\Object\ClassDefinition\Data\Select();
                }
                $definition->setName($keyName);
                $definition = json_encode($definition);
//                var_dump($definition);
                $keyConfig->setDefinition($definition); // The definition is used in object editor to render fields
                $keyConfig->save();
            }


            $keygroupconfig = new \Pimcore\Model\Object\Classificationstore\KeyGroupRelation();
            $keygroupconfig->setKeyId($keyConfig->getId());
            $keygroupconfig->setGroupId($i < 3 ? $group1->getId() : $group2->getId());
            $keygroupconfig->setSorter($i);
            $keygroupconfig->save();
        }
    }

    protected function setUp()
    {
        parent::setUp();

        if (!\Pimcore\Model\Object\ClassDefinition::getByName("csstoreclass")) {
            echo "Create class ...\n";
            $json = file_get_contents(TESTS_PATH . "/resources/objects/classificationstore.json");

            $class = new \Pimcore\Model\Object\ClassDefinition();
            $class->setName("csstoreclass");
            $class->setUserOwner(1);

            \Pimcore\Model\Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
            /** @var  $fd \Pimcore\Model\Object\ClassDefinition\Data\Classificationstore*/
            $fd = $class->getFieldDefinition("csstore");

            $store = \Pimcore\Model\Object\Classificationstore\StoreConfig::getByName("teststore");
            if (!$store) {
                $store = new \Pimcore\Model\Object\Classificationstore\StoreConfig();
                $store->setName("teststore");
                $store->save();
            }

            $fd->setStoreId($store->getId());
            $class->save();

            $this->configureStore($store);
        }
    }
}
