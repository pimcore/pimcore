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
use Pimcore\Google\Analytics as AnalyticsHelper;
use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class GoogleAnalyticsCodeListener extends AbstractFrontendListener
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
        // only inject analytics code on non-admin requests
        if (!$this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $event->getResponse();

        if (\Pimcore\Tool::useFrontendOutputFilters()) {
            if ($event->isMasterRequest() && $this->isHtmlResponse($response)) {
                if ($this->enabled && $code = AnalyticsHelper::getCode()) {

                    // analytics
                    $content = $response->getContent();

                    // search for the end <head> tag, and insert the google analytics code before
                    // this method is much faster than using simple_html_dom and uses less memory
                    $headEndPosition = strripos($content, "</head>");
                    if ($headEndPosition !== false) {
                        $content = substr_replace($content, $code . "</head>", $headEndPosition, 7);
                    }

                    $response->setContent($content);
                }
            }
        }
    }
}
