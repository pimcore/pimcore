<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Http\ResponseHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpCacheListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    /**
     * @param RequestHelper $requestHelper
     * @param ResponseHelper $responseHelper
     */
    public function __construct(RequestHelper $requestHelper, ResponseHelper $responseHelper)
    {
        $this->requestHelper  = $requestHelper;
        $this->responseHelper = $responseHelper;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        $disable = false;
        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            $disable = true;
        } else {
            if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
                $disable = true;
            }

            if (\Pimcore::inDebugMode()) {
                $disable = true;
            }
        }

        $response = $event->getResponse();

        if ($response && $disable) {
            // set headers to avoid problems with proxies, ...
            $this->responseHelper->disableCache($response, false);
        }
    }
}
