<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 14.05.13
 * Time: 20:54
 */
class Test_BaseRest extends Test_Base{

    public static function getRestClient(){
        $testConfig = new Zend_Config_Xml(TESTS_PATH . "/config/testconfig.xml");
        $testConfig = $testConfig->toArray();

        $client = new Pimcore_Tool_RestClient();
        $client->enableTestMode();
        $client->setBaseUrl("http://" . $testConfig["rest"]["host"] . $testConfig["rest"]["base"]);
        $client->setHost($testConfig["rest"]["host"]);
        return $client;
    }

}