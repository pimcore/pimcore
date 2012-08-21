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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Google_Api {

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

        $client = new apiClient(array(
            "ioFileCache_directory" => PIMCORE_CACHE_DIRECTORY
        ));
        $client->setApplicationName("pimcore CMF");

        $key = file_get_contents(self::getPrivateKeyPath());
        $client->setAssertionCredentials(new apiAssertionCredentials(
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
            file_put_contents($tokenFile, $token);
        }

        $client->setAccessToken($token);
        return $client;
    }

    public static function getSimpleClient() {

        if(!self::isSimpleConfigured()) {
            return false;
        }

        self::loadClientLibrary();

        $client = new apiClient(array(
            "ioFileCache_directory" => PIMCORE_CACHE_DIRECTORY
        ));
        $client->setApplicationName("pimcore CMF");
        $client->setDeveloperKey(Pimcore_Config::getSystemConfig()->services->google->simpleapikey);

        return $client;
    }

    /**
     * load the client libs dynamically, otherwise just the initialization will raise an exception
     * see: http://www.pimcore.org/issues/browse/PIMCORE-1641
     * @static
     */
    private static function loadClientLibrary() {
        include_once("googleApiClient/apiClient.php");
        include_once("googleApiClient/contrib/apiAnalyticsService.php");
        include_once("googleApiClient/contrib/apiSiteVerificationService.php");
        include_once("googleApiClient/contrib/apiCustomsearchService.php");
    }
}
