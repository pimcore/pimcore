<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class GoogleSearchConsoleVerificationListener extends AbstractFrontendListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent  $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

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
