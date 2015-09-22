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

class Webmastertools extends \Zend_Controller_Plugin_Abstract {

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {
            
        $conf = \Pimcore\Config::getReportConfig();
        if($conf->webmastertools->sites) {
            $sites = $conf->webmastertools->sites->toArray();
            
            if(is_array($sites)) {
                foreach ($sites as $site) {
                    if($site["verification"]) {
                       if($request->getRequestUri() == ("/".$site["verification"])) {
                            echo "google-site-verification: " . $site["verification"];
                            exit;
                        }
                    }
                }
            }
        }
    }
}
