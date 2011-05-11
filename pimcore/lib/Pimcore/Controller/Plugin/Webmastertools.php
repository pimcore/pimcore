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

class Pimcore_Controller_Plugin_Webmastertools extends Zend_Controller_Plugin_Abstract {

    public function routeStartup(Zend_Controller_Request_Abstract $request) {
            
        $conf = Pimcore_Config::getReportConfig();
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
