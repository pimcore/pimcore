<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Google;

use Pimcore\Config;
use Pimcore\Model\Tool\TmpStore;

class Api {

    /**
     *
     */
    const ANALYTICS_API_URL = 'https://www.googleapis.com/analytics/v3/';

    /**
     * @return string
     */
    public static function getPrivateKeyPath() {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/google-api-private-key.p12";
    }

    /**
     * @return mixed
     */
    public static function getConfig () {
        return Config::getSystemConfig()->services->google;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isConfigured($type = "service") {
        if($type == "simple") {
            return self::isSimpleConfigured();
        }

        return self::isServiceConfigured();
    }

    /**
     * @return bool
     */
    public static function isServiceConfigured() {
        $config = self::getConfig();

        if($config->client_id && $config->email && file_exists(self::getPrivateKeyPath())) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function isSimpleConfigured() {
        $config = self::getConfig();

        if($config->simpleapikey) {
            return true;
        }
        return false;
    }

    /**
     * @param string $type
     * @return \Google_Client
     */
    public static function getClient($type = "service") {
        if($type == "simple") {
            return self::getSimpleClient();
        }

        return self::getServiceClient();
    }

    /**
     * @return \Google_Client
     * @throws \Zend_Json_Exception
     */
    public static function getServiceClient () {

        if(!self::isServiceConfigured()) {
            return false;
        }

        $config = self::getConfig();

        $clientConfig = new \Google_Config();
        $clientConfig->setClassConfig("Google_Cache_File", "directory", PIMCORE_CACHE_DIRECTORY);

        $client = new \Google_Client($clientConfig);
        $client->setApplicationName("pimcore CMF");

        $key = file_get_contents(self::getPrivateKeyPath());
        $client->setAssertionCredentials(new \Google_Auth_AssertionCredentials(
            $config->email,
            array('https://www.googleapis.com/auth/analytics.readonly',"https://www.google.com/webmasters/tools/feeds/"),
            $key)
        );

        $client->setClientId($config->client_id);

        // token cache
        $tokenId =  "google-api.token";
        if($tokenData = TmpStore::get($tokenId)) {
            $tokenInfo = \Zend_Json::decode($tokenData->getData());
            if( ($tokenInfo["created"] + $tokenInfo["expires_in"]) > (time()-900) )  {
                $token = $tokenData->getData();
            }
        }

        if(!$token) {
            $client->getAuth()->refreshTokenWithAssertion();
            $token = $client->getAuth()->getAccessToken();

            // 1 hour (3600s) is the default expiry time
            TmpStore::add($tokenId, $token, null, 3600);
        }

        $client->setAccessToken($token);
        return $client;
    }

    /**
     * @return \Google_Client
     */
    public static function getSimpleClient() {

        if(!self::isSimpleConfigured()) {
            return false;
        }

        $clientConfig = new \Google_Config();
        $clientConfig->setClassConfig("Google_Cache_File", "directory", PIMCORE_CACHE_DIRECTORY);

        $client = new \Google_Client($clientConfig);
        $client->setApplicationName("pimcore CMF");
        $client->setDeveloperKey(Config::getSystemConfig()->services->google->simpleapikey);

        return $client;
    }

    /**
     * @return array
     */
    public static function getAnalyticsDimensions() {
        return self::getAnalyticsMetadataByType('DIMENSION');
    }

    /**
     * @return array
     */
    public static function getAnalyticsMetrics() {
        return self::getAnalyticsMetadataByType('METRIC');
    }

    /**
     * @return mixed
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Json_Exception
     */
    public static function getAnalyticsMetadata() {
        $client = \Pimcore\Tool::getHttpClient();
        $client->setUri(self::ANALYTICS_API_URL.'metadata/ga/columns');

        $result = $client->request();
        return \Zend_Json::decode($result->getBody());
    }

    /**
     * @param $type
     * @return array
     * @throws \Zend_Exception
     */
    protected static function getAnalyticsMetadataByType($type) {
        $data = self::getAnalyticsMetadata();
        $t = \Zend_Registry::get("Zend_Translate");

        $result = array();
        foreach($data['items'] as $item) {
            if($item['attributes']['type'] == $type) {

                if(strpos($item['id'], 'XX') !== false) {
                    for($i = 1; $i<=5; $i++) {
                        $name = str_replace('1', $i, str_replace('01', $i, $t->translate($item['attributes']['uiName'])));

                        if(in_array($item['id'], array('ga:dimensionXX', 'ga:metricXX'))) {
                            $name .= ' '.$i;
                        }
                        $result[] = array(
                            'id'=>str_replace('XX', $i, $item['id']),
                            'name'=>$name
                        );
                    }
                } else {
                    $result[] = array(
                        'id'=>$item['id'],
                        'name'=>$t->translate($item['attributes']['uiName'])
                    );
                }
            }
        }

        return $result;
    }
}
