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

include_once("googleApiClient/apiClient.php");
include_once("googleApiClient/contrib/apiAnalyticsService.php");
include_once("googleApiClient/contrib/apiSiteVerificationService.php");

class Pimcore_Google_Api {

    public static function getPrivateKeyPath() {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/google-api-private-key.p12";
    }

    public static function getConfig () {
        return Pimcore_Config::getSystemConfig()->services->google;
    }

    public static function isConfigured() {

        $config = self::getConfig();

        if($config->client_id && $config->email && file_exists(self::getPrivateKeyPath())) {
            return true;
        }

        return false;
    }

    public static function getClient() {

        if(!self::isConfigured()) {
            return false;
        }

        $config = self::getConfig();

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
}
