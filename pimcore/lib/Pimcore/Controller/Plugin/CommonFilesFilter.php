<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        "@^/wpad.dat$@",
        "@^/.crl$@",
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
                    while (@ob_end_flush()) ;

                    header("Content-Type: text/plain; charset=utf8");
                    readfile($robotsPath);
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
