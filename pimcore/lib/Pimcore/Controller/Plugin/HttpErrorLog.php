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

namespace Pimcore\Controller\Plugin;

class HttpErrorLog extends \Zend_Controller_Plugin_Abstract {

    /**
     *
     */
    public function dispatchLoopShutdown() {

        $code = (string) $this->getResponse()->getHttpResponseCode();
        if($code && ($code[0] == "4" || $code[0] == "5")) {
            $this->writeLog();

            // put the response into the cache, this is read in Pimcore_Controller_Action_Frontend::checkForErrors()
            $responseData = $this->getResponse()->getBody();
            if(strlen($responseData) > 20) {
                $cacheKey = "error_page_response_" . \Pimcore\Tool\Frontend::getSiteKey();
                \Pimcore\Model\Cache::save($responseData, $cacheKey, array("output"), 900, 9992);
            }
        }
    }

    /**
     *
     */
    public function writeLog () {

        $code = (string) $this->getResponse()->getHttpResponseCode();
        $db = \Pimcore\Resource::get();

        try {
            $db->insert("http_error_log", array(
                "path" => $this->getRequest()->getPathInfo(),
                "code" => (int) $code,
                "parametersGet" => serialize($_GET),
                "parametersPost" => serialize($_POST),
                "cookies" => serialize($_COOKIE),
                "serverVars" => serialize($_SERVER),
                "date" => time()
            ));
        } catch (\Exception $e) {
            \Logger::error("Unable to log http error");
            \Logger::error($e);
        }
    }
}
