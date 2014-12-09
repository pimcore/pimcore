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

use Pimcore\Model\Tool;

class QrCode extends \Zend_Controller_Plugin_Abstract {

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {

        if(preg_match("@^/qr~-~code/([a-zA-Z0-9_\-]+)@",$request->getPathInfo(), $matches)) {
            if(array_key_exists(1, $matches) && !empty($matches[1])) {
                try {
                    $code = Tool\Qrcode\Config::getByName($matches[1]);
                    $url = $code->getUrl();
                    if($code->getGoogleAnalytics()) {
                        $glue = "?";
                        if(strpos($url, "?")) {
                            $glue = "&";
                        }

                        $url .= $glue;
                        $url .= "utm_source=Mobile&utm_medium=QR-Code&utm_campaign=" . $code->getName();
                    }

                    header("Location: " . $url, true, 302);
                    exit;

                } catch (\Exception $e) {
                    // nothing to do
                    \Logger::error("called an QR code but '" . $matches[1] . " is not a code in the system.");
                }
            }
        }
    }
}
