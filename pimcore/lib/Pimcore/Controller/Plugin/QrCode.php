<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
                $code = Tool\Qrcode\Config::getByName($matches[1]);
                if($code) {
                    $url = $code->getUrl();
                    if ($code->getGoogleAnalytics()) {
                        $glue = "?";
                        if (strpos($url, "?")) {
                            $glue = "&";
                        }

                        $url .= $glue;
                        $url .= "utm_source=Mobile&utm_medium=QR-Code&utm_campaign=" . $code->getName();
                    }

                    header("Location: " . $url, true, 302);
                    exit;
                } else {
                    \Logger::error("called an QR code but '" . $matches[1] . " is not a code in the system.");
                }
            }
        }
    }
}
