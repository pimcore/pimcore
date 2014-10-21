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
use Pimcore\Google\Analytics as AnalyticsHelper;

class Analytics extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request) {
        if(!Tool::useFrontendOutputFilters($request)) {
            return $this->disable();
        }
    }

    /**
     * @return bool
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {
        
        if(!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled && $code = AnalyticsHelper::getCode()) {
            
            // analytics
            $body = $this->getResponse()->getBody();

            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = stripos($body, "</head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);
        }
    }
}
