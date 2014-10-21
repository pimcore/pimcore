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

namespace Pimcore\Helper;

use Pimcore\Tool; 

class SocialMedia {

    /**
     * @param string|array $urls
     * @return array
     */
    public static function getFacebookShares($urls) {

        $urls = (array) $urls;

        $data  = Tool::getHttpData("https://graph.facebook.com/", array(
            "ids" => implode(",",$urls)
        ));

        $results = array();

        if($data) {
            $data = \Zend_Json::decode($data);
            if($data) {
                foreach($data as $d) {
                    $shares = array_key_exists("shares", $d) ? $d["shares"] : 0;
                    $results[$d["id"]] = $shares;
                }
            }
        }

        return $results;
    }

    /**
     * @param $urls
     * @return array
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public static function getGooglePlusShares($urls) {

        $urls = (array) $urls;

        $results = array();

        foreach ($urls as $url) {
            $client = Tool::getHttpClient();
            $client->setUri("https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ");
            $client->setHeaders("Content-Type", "application/json");
            $client->setRawData('[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');

            try {
                $response = $client->request(\Zend_Http_Client::POST);
                if($response->isSuccessful()) {
                    $data = $response->getBody();
                    if($data) {
                        $data = \Zend_Json::decode($data);
                        if($data) {
                            if(array_key_exists(0, $data)) {
                                $results[$data[0]['result']["id"]] = $data[0]['result']['metadata']['globalCounts']['count'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return $results;
    }
}
