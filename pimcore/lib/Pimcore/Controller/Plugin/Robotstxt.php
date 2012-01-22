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

class Pimcore_Controller_Plugin_Robotstxt extends Zend_Controller_Plugin_Abstract {

    public function routeStartup(Zend_Controller_Request_Abstract $request) {
        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots.txt";
        if($request->getRequestUri() == "/robots.txt") {
            if(is_file($robotsPath)) {
                header("Content-Type: text/plain; charset=utf8");
                echo file_get_contents($robotsPath);
                exit;
            }
        }
    }
}
