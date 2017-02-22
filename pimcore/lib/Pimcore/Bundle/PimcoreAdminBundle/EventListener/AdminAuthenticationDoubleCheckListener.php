<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\RequestMatcherFactory;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles double authentication check for pimcore controllers after the firewall did to make sure the admin interface is
 * not accessible on configuration errors. Unauthenticated routes are not double-checked (e.g. login).
 *
 * TODO: the double authentication check is currently running for every AdminControllerInterface, independent of the request
 * context, to ensure third party bundles using the AdminController handle authentication as well. Should we do this on the
 * request context instead?
 */
class AdminAuthenticationDoubleCheckListener extends AbstractAdminControllerListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherFactory
     */
    protected $requestMatcherFactory;

    /**
     * @var array
     */
    protected $unauthenticatedRoutes;

    /**
     * @var RequestMatcherInterface[]
     */
    protected $unauthenticatedMatchers;

    /**
     * @param RequestMatcherFactory $factory
     * @param array $unauthenticatedRoutes
     */
    public function __construct(RequestMatcherFactory $factory, array $unauthenticatedRoutes)
    {
        $this->requestMatcherFactory = $factory;
        $this->unauthenticatedRoutes = $unauthenticatedRoutes;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$this->isAdminController($event)) {
            return;
        }

        $request = $event->getRequest();

        // double check we have a valid user to make sure there is no invalid security config
        // opening admin interface to the public
        if ($this->requestNeedsAuthentication($request)) {
            $this->checkSecurityUser();
        }
    }

    /**
     * Check if the current request needs double authentication
     *
     * @param Request $request
     * @return bool
     */
    protected function requestNeedsAuthentication(Request $request)
    {
        foreach ($this->getUnauthenticatedMatchers() as $matcher) {
            if ($matcher->matches($request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of paths which don't need double authentication check
     *
     * @return RequestMatcherInterface[]
     */
    protected function getUnauthenticatedMatchers()
    {
        if (null === $this->unauthenticatedMatchers) {
            $this->unauthenticatedMatchers = $this->requestMatcherFactory->buildRequestMatchers($this->unauthenticatedRoutes);
        }

        return $this->unauthenticatedMatchers;
    }

    /**
     * @throws AccessDeniedHttpException
     *      if there's no current user in the session
     */
    protected function checkSecurityUser()
    {
        $user = Authentication::authenticateSession();
        if (null === $user) {
            throw new AccessDeniedHttpException('Invalid user');
        }
    }
}
