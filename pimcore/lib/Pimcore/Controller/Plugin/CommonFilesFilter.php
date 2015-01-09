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

use Pimcore\Tool;
use Pimcore\Model\Site;

class CommonFilesFilter extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var array
     */
    public static $files = array(
        "@^/robots.txt$@",
        "@^/crossdomain.xml$@",
        "@^/favicon.ico$@",
        "@^/apple-touch-icon@",
        "@^/browserconfig.xml$@",
    );

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        $found = false;
        foreach(self::$files as $pattern) {
            if(preg_match($pattern, $request->getPathInfo())) {
                $found = true;
                break;
            }
        }

        if($found) {
            if($request->getPathInfo() == "/robots.txt") {

                // check for site
                try {
                    $domain = Tool::getHostname();
                    $site = Site::getByDomain($domain);
                } catch (\Exception $e) { }

                $siteSuffix = "";
                if($site instanceof Site) {
                    $siteSuffix = "-" . $site->getId();
                }

                // check for configured robots.txt in pimcore
                $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots" . $siteSuffix . ".txt";
                if(is_file($robotsPath)) {
                    header("Content-Type: text/plain; charset=utf8");
                    echo file_get_contents($robotsPath);
                    exit;
                }
            }

            // if no other rule matches, exit anyway with a 404, to prevent the error page to be shown
            header('HTTP/1.1 404 Not Found');
            echo "HTTP/1.1 404 Not Found\nFiltered by common files filter";
            exit;
        }
    }
}
