<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 14.05.13
 * Time: 20:54
 */
class Test_BaseRest extends Test_Base
{
    protected static $testConfig;

    public static function getRestClient()
    {
        $testConfig = self::getTestConfig();

        $client = new Pimcore_Tool_RestClient();
        $client->enableTestMode();
        $client->setBaseUrl("http://" . $testConfig["rest"]["host"] . $testConfig["rest"]["base"]);
        $client->setHost($testConfig["rest"]["host"]);

        return $client;
    }

    /**
     * @return mixed
     */
    public static function getTestConfig()
    {
        if (!self::$testConfig) {
            $testConfig = new \Pimcore\Config\Config(xmlToArray(TESTS_PATH . "/config/testconfig.xml"));
            $testConfig = $testConfig->toArray();
            self::$testConfig = $testConfig;
        }

        return self::$testConfig;
    }

    /**
     * @param mixed $testConfig
     */
    public static function setTestConfig($testConfig)
    {
        self::$testConfig = $testConfig;
    }
}
