<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\AbstractEventListener\ResponseInjection;
use Pimcore\Google\Analytics as AnalyticsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class GoogleAnalyticsCodeListener extends ResponseInjection implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [];

        if (\Pimcore\Tool::isFrontend() && !\Pimcore\Tool::isFrontentRequestByAdmin()) {
            $events = [
                KernelEvents::RESPONSE => ['onKernelResponse']
            ];
        }

        return $events;
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

        if ($this->isHtmlResponse($response)) {
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
