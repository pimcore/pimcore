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

class Pimcore_Controller_Plugin_CommonFilesFilter extends Zend_Controller_Plugin_Abstract {

    /**
     * @var array
     */
    public static $files = array(
        "/robots.txt",
        "/crossdomain.xml",
        "/favicon.ico"
    );

    /**
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        if(in_array($request->getPathInfo(), self::$files)) {

            // check for configured robots.txt in pimcore
            $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots.txt";
            if($request->getPathInfo() == "/robots.txt") {
                if(is_file($robotsPath)) {
                    header("Content-Type: text/plain; charset=utf8");
                    echo file_get_contents($robotsPath);
                    exit;
                }
            }

            // if no other rule matches, exit anyway with a 404, to prevent the error page to be shown
            header('HTTP/1.1 404 Not Found');
            echo "HTTP/1.1 404 Not Found";
            exit;
        }
    }
}
