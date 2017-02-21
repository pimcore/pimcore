<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminControllerInterface;
use Pimcore\Event\Admin\UnauthenticatedRequestWhitelistEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Tool\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles double authentication check for pimcore controllers and injects the admin session into the request.
 *
 * TODO: if `$request->getSession()` is used before this is called there will be a reference to the global symfony session
 * (or null if sessions are not configured). Is this early enough here or do we need to adapt the SessionListener (core)?
 */
class AdminControllerListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $whitelist;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $callable = $event->getController();

        if (!is_array($callable) || count($callable) === 0) {
            return;
        }

        $controller = $callable[0];
        if (!($controller instanceof AdminControllerInterface)) {
            return;
        }

        $request = $event->getRequest();

        // double check we have a valid user to make sure there is no invalid security config
        // opening admin interface to the public
        if ($this->requestNeedsAuthentication($request)) {
            $this->checkSecurityUser();
        }

        // set request session to admin session (pimcore_admin_sid)
        $request->setSession($this->container->get('pimcore_admin.session'));
    }

    /**
     * Check if the current request needs double authentication
     *
     * @param Request $request
     * @return bool
     */
    protected function requestNeedsAuthentication(Request $request)
    {
        $whitelist = $this->getRequestWhitelist();

        foreach ($whitelist as $path) {
            if (false !== strpos(rawurldecode($request->getPathInfo()), $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of paths which don't need double authentication check
     *
     * @return array
     */
    protected function getRequestWhitelist()
    {
        if (null === $this->whitelist) {
            $event = new UnauthenticatedRequestWhitelistEvent([
                '/admin/login'
            ]);

            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch(AdminEvents::UNAUTHENTICATED_REQUEST_WHITELIST, $event);

            $this->whitelist = $event->getWhitelist();
        }

        return $this->whitelist;
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
