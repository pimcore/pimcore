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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Google_Api {

    const ANALYTICS_API_URL = 'https://www.googleapis.com/analytics/v3/';

    public static function getPrivateKeyPath() {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/google-api-private-key.p12";
    }

    public static function getConfig () {
        return Pimcore_Config::getSystemConfig()->services->google;
    }

    public static function isConfigured($type = "service") {
        if($type == "simple") {
            return self::isSimpleConfigured();
        }

        return self::isServiceConfigured();
    }

    public static function isServiceConfigured() {
        $config = self::getConfig();

        if($config->client_id && $config->email && file_exists(self::getPrivateKeyPath())) {
            return true;
        }
        return false;
    }

    public static function isSimpleConfigured() {
        $config = self::getConfig();

        if($config->simpleapikey) {
            return true;
        }
        return false;
    }

    public static function getClient($type = "service") {
        if($type == "simple") {
            return self::getSimpleClient();
        }

        return self::getServiceClient();
    }

    public static function getServiceClient () {

        if(!self::isServiceConfigured()) {
            return false;
        }

        $config = self::getConfig();
        self::loadClientLibrary();

        $client = new Google_Client(array(
            "ioFileCache_directory" => PIMCORE_CACHE_DIRECTORY
        ));
        $client->setApplicationName("pimcore CMF");

        $key = file_get_contents(self::getPrivateKeyPath());
        $client->setAssertionCredentials(new Google_AssertionCredentials(
            $config->email,
            array('https://www.googleapis.com/auth/analytics.readonly',"https://www.google.com/webmasters/tools/feeds/"),
            $key)
        );

        $client->setClientId($config->client_id);

        // token cache
        $tokenFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/google-api.token";
        if(file_exists($tokenFile)) {
            $tokenData = file_get_contents($tokenFile);
            $tokenInfo = Zend_Json::decode($tokenData);
            if( ($tokenInfo["created"] + $tokenInfo["expires_in"]) > (time()-900) )  {
                $token = $tokenData;
            }
        }

        if(!$token) {
            $client->getAuth()->refreshTokenWithAssertion();
            $token = $client->getAuth()->getAccessToken();
            Pimcore_File::put($tokenFile, $token);
        }

        $client->setAccessToken($token);
        return $client;
    }

    public static function getSimpleClient() {

        if(!self::isSimpleConfigured()) {
            return false;
        }

        self::loadClientLibrary();

        $client = new Google_Client(array(
            "ioFileCache_directory" => PIMCORE_CACHE_DIRECTORY
        ));
        $client->setApplicationName("pimcore CMF");
        $client->setDeveloperKey(Pimcore_Config::getSystemConfig()->services->google->simpleapikey);

        return $client;
    }

    public static function getAnalyticsDimensions() {
        return self::getAnalyticsMetadataByType('DIMENSION');
    }

    public static function getAnalyticsMetrics() {
        return self::getAnalyticsMetadataByType('METRIC');
    }

    public static function getAnalyticsMetadata() {
        $client = Pimcore_Tool::getHttpClient();
        $client->setUri(self::ANALYTICS_API_URL.'metadata/ga/columns');

        $result = $client->request();
        return Zend_Json::decode($result->getBody());
    }

    protected static function getAnalyticsMetadataByType($type) {
        $data = self::getAnalyticsMetadata();
        $t = Zend_Registry::get("Zend_Translate");

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

    /**
     * load the client libs dynamically, otherwise just the initialization will raise an exception
     * see: http://www.pimcore.org/issues/browse/PIMCORE-1641
     * @static
     */
    private static function loadClientLibrary() {
        include_once("googleApiClient/Google_Client.php");
        include_once("googleApiClient/contrib/Google_AnalyticsService.php");
        include_once("googleApiClient/contrib/Google_SiteVerificationService.php");
        include_once("googleApiClient/contrib/Google_CustomsearchService.php");
    }
}
