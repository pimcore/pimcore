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

class GoogleTagManager extends \Zend_Controller_Plugin_Abstract
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
        // It's standard industry practice to exclude tracking if the request includes the header 'X-Purpose:preview'
        if ($this->getRequest()->getHeader('X-Purpose') == 'preview') {
            return;
        }

        if (!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        $siteKey = \Pimcore\Tool\Frontend::getSiteKey();
        $reportConfig = \Pimcore\Config::getReportConfig();

        if ($this->enabled && isset($reportConfig->tagmanager->sites->$siteKey->containerId)) {
            $containerId = $reportConfig->tagmanager->sites->$siteKey->containerId;

            if ($containerId) {
                $codeHead = <<<CODE
\n\n<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','$containerId');</script>
<!-- End Google Tag Manager -->\n\n
CODE;

                $codeBody = <<<CODE
\n\n<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=$containerId"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->\n\n
CODE;


                $body = $this->getResponse()->getBody();

                // search for the end <head> tag, and insert the google tag manager code before
                // this method is much faster than using simple_html_dom and uses less memory
                $headEndPosition = stripos($body, "</head>");
                if ($headEndPosition !== false) {
                    $body = substr_replace($body, $codeHead."</head>", $headEndPosition, 7);
                }

                // insert code after the opening <body> tag
                $body = preg_replace("@<body(>|.*?[^?]>)@", "<body$1\n\n" . $codeBody, $body);

                $this->getResponse()->setBody($body);
            }
        }
    }
}
