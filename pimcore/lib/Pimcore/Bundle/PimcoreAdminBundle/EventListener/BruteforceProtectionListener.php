<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\BruteforceProtectedControllerInterface;
use Pimcore\Bundle\PimcoreAdminBundle\Security\BruteforceProtectionHandler;
use Pimcore\Bundle\PimcoreAdminBundle\Security\Exception\BruteforceProtectionException;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BruteforceProtectionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var BruteforceProtectionHandler
     */
    protected $handler;

    /**
     * @param BruteforceProtectionHandler $handler
     */
    public function __construct(BruteforceProtectionHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::EXCEPTION  => 'onKernelException'
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $callable = $event->getController();
        if (is_array($callable)) {
            $controller = $callable[0];
            if ($controller instanceof BruteforceProtectedControllerInterface) {
                $this->handler->checkProtection($request->get('username'), $request);
            }
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // handle brute force exception and return a plaintext response
        $e = $event->getException();
        if ($e instanceof BruteforceProtectionException) {
            $event->setResponse(new Response($e->getMessage()));
        }
    }
}
