<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\AbstractEventListener\ResponseInjection;
use Pimcore\Google\Analytics as AnalyticsHelper;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InternalWysiwygHtmlAttributeFilter extends ResponseInjection
{
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
        $response = $event->getResponse();

        if (Tool::useFrontendOutputFilters()) {
            $content = $response->getContent();
            $content = preg_replace("/ pimcore_(id|type|disable_thumbnail)=\\\"([0-9a-z]+)\\\"/", "", $content);
            $response->setContent($content);
        }
    }
}
