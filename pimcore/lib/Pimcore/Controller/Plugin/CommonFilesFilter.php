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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Plugin;

use Pimcore\Tool;
use Pimcore\Model\Site;

class CommonFilesFilter extends \Zend_Controller_Plugin_Abstract
{

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
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        $found = false;
        foreach (self::$files as $pattern) {
            if (preg_match($pattern, $request->getPathInfo())) {
                $found = true;
                break;
            }
        }

        if ($found) {
            if ($request->getPathInfo() == "/robots.txt") {

                // check for site
                try {
                    $domain = Tool::getHostname();
                    $site = Site::getByDomain($domain);
                } catch (\Exception $e) {
                }

                $siteSuffix = "";
                if ($site instanceof Site) {
                    $siteSuffix = "-" . $site->getId();
                }

                // send correct headers
                header("Content-Type: text/plain; charset=utf8");
                while (@ob_end_flush()) ;

                // check for configured robots.txt in pimcore
                $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots" . $siteSuffix . ".txt";
                if (is_file($robotsPath)) {
                    readfile($robotsPath);
                } else {
                    echo "User-agent: *\nDisallow:"; // default behavior
                }

                exit;
            }

            // if no other rule matches, exit anyway with a 404, to prevent the error page to be shown
            header('HTTP/1.1 404 Not Found');
            echo "HTTP/1.1 404 Not Found\nFiltered by common files filter";
            exit;
        }
    }
}
