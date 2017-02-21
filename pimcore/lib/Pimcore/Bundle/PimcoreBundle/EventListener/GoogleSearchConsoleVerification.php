<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\AbstractEventListener\ResponseInjection;
use Pimcore\Google\Analytics as AnalyticsHelper;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

class GoogleSearchConsoleVerification extends ResponseInjection
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent  $event)
    {
        if ($event->isMasterRequest()) {
            $conf = \Pimcore\Config::getReportConfig();

            if (!is_null($conf->webmastertools) && isset($conf->webmastertools->sites)) {
                $sites = $conf->webmastertools->sites->toArray();

                if (is_array($sites)) {
                    foreach ($sites as $site) {
                        if ($site["verification"]) {
                            $request = $event->getRequest();
                            if ($request->getPathInfo() == ("/".$site["verification"])) {

                                $response = new Response("google-site-verification: " . $site["verification"], 503);
                                $event->setResponse($response);
                            }
                        }
                    }
                }
            }
        }
    }
}
