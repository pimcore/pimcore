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
use Pimcore\Google\Analytics as AnalyticsHelper;

class Analytics extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request)
    {
        if (!Tool::useFrontendOutputFilters($request)) {
            return $this->disable();
        }
    }

    /**
     * @return bool
     */
    public function disable()
    {
        $this->enabled = false;

        return true;
    }

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        if (!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        // It's standard industry practice to exclude tracking if the request includes the header 'X-Purpose:preview'
        if($this->getRequest()->getHeader('X-Purpose') == 'preview') {
            return;
        }

        if ($this->enabled && $code = AnalyticsHelper::getCode()) {

            // analytics
            $body = $this->getResponse()->getBody();

            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = stripos($body, "</head>");
            if ($headEndPosition !== false) {
                $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);
        }
    }
}
