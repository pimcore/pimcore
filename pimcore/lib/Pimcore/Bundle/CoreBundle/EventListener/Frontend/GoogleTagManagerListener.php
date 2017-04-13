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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class GoogleTagManagerListener extends AbstractFrontendListener
{
    use ResponseInjectionTrait;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @return bool
     */
    public function disable()
    {
        $this->enabled = false;

        return true;
    }

    /**
     * @return bool
     */
    public function enable()
    {
        $this->enabled = true;

        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $event->getResponse();

        $siteKey = \Pimcore\Tool\Frontend::getSiteKey();
        $reportConfig = \Pimcore\Config::getReportConfig();

        if ($this->isEnabled() && $event->isMasterRequest() && $this->isHtmlResponse($response) &&
             Tool::useFrontendOutputFilters() && isset($reportConfig->tagmanager->sites->$siteKey->containerId)) {
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

                $content = $response->getContent();

                // search for the end <head> tag, and insert the google tag manager code before
                // this method is much faster than using simple_html_dom and uses less memory
                $headEndPosition = stripos($content, '</head>');
                if ($headEndPosition !== false) {
                    $content = substr_replace($content, $codeHead.'</head>', $headEndPosition, 7);
                }

                // insert code after the opening <body> tag
                $content = preg_replace('@<body(>|.*?[^?]>)@', "<body$1\n\n" . $codeBody, $content);

                $response->setContent($content);
            }
        }
    }
}
