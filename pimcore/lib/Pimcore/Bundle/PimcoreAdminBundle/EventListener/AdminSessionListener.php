<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminControllerInterface;
use Pimcore\Bundle\PimcoreAdminBundle\EventListener\Traits\ControllerTypeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injects the admin session into the request.
 *
 * TODO: if `$request->getSession()` is used before this is called there will be a reference to the global symfony session
 * (or null if sessions are not configured). Is this early enough here or do we need to adapt the SessionListener (core)?
 */
class AdminSessionListener implements EventSubscriberInterface
{
    use ControllerTypeTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

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
        if (!$this->isControllerType($event, AdminControllerInterface::class)) {
            return;
        }

        // set request session to admin session (pimcore_admin_sid) instead of using the
        // framework wide one (if configured)
        $event->getRequest()->setSession($this->container->get('pimcore_admin.session'));
    }
}
