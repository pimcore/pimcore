<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Config;
use Pimcore\Tool;

class Legacy {


    /**
     * @param string $type
     * @param array $options
     * @return \Zend_Http_Client
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public static function getHttpClient($type = "Zend_Http_Client", $options = [])
    {
        $config = Config::getSystemConfig();
        $clientConfig = $config->httpclient->toArray();
        $clientConfig["adapter"] =  (isset($clientConfig["adapter"]) && !empty($clientConfig["adapter"])) ? $clientConfig["adapter"] : "Zend_Http_Client_Adapter_Socket";
        $clientConfig["maxredirects"] =  isset($options["maxredirects"]) ? $options["maxredirects"] : 2;
        $clientConfig["timeout"] =  isset($options["timeout"]) ? $options["timeout"] : 3600;
        $type = empty($type) ? "Zend_Http_Client" : $type;

        $type = "\\" . ltrim($type, "\\");

        if (Tool::classExists($type)) {
            $client = new $type(null, $clientConfig);

            // workaround/for ZF (Proxy-authorization isn't added by ZF)
            if ($clientConfig['proxy_user']) {
                $client->setHeaders('Proxy-authorization', \Zend_Http_Client::encodeAuthHeader(
                    $clientConfig['proxy_user'], $clientConfig['proxy_pass'], \Zend_Http_Client::AUTH_BASIC
                ));
            }
        } else {
            throw new \Exception("Pimcore_Tool::getHttpClient: Unable to create an instance of $type");
        }

        return $client;
    }



}
