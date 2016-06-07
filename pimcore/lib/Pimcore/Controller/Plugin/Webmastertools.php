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

class Webmastertools extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {
        $conf = \Pimcore\Config::getReportConfig();
        if (!is_null($conf->webmastertools) && isset($conf->webmastertools->sites)) {
            $sites = $conf->webmastertools->sites->toArray();
            
            if (is_array($sites)) {
                foreach ($sites as $site) {
                    if ($site["verification"]) {
                        if ($request->getRequestUri() == ("/".$site["verification"])) {
                            echo "google-site-verification: " . $site["verification"];
                            exit;
                        }
                    }
                }
            }
        }
    }
}
