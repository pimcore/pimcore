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

class Pimcore_Controller_Plugin_HttpErrorLog extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopShutdown() {

        $code = (string) $this->getResponse()->getHttpResponseCode();
        if($code && ($code[0] == "4" || $code[0] == "5")) {
            $db = Pimcore_Resource::get();

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
            } catch (Exception $e) {
                Logger::error("Unable to log http error");
                Logger::error($e);
            }
        }
    }
}
