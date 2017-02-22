<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\AbstractEventListener\ResponseInjection;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Google\Analytics as AnalyticsHelper;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class GoogleAnalyticsCodeListener extends ResponseInjection
{
    use PimcoreContextAwareTrait;

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
