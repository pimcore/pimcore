<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PimcoreContextListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PimcoreContextResolver
     */
    protected $resolver;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param PimcoreContextResolver $resolver
     * @param RequestStack $requestStack
     */
    public function __construct(PimcoreContextResolver $resolver, RequestStack $requestStack)
    {
        $this->resolver     = $resolver;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            // run after router to be able to match the _route attribute
            // TODO check if this is early enough
            KernelEvents::REQUEST => ['onKernelRequest', 24]
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($event->isMasterRequest()) {
            // if there's no pimcore context set on the request, try to guess and set it
            if (!$this->resolver->getPimcoreContext($request)) {
                $context = $this->resolver->guessPimcoreContext($request);

                if ($context) {
                    $this->resolver->setPimcoreContext($request, $context);

                    $this->logger->debug('Resolved pimcore context for path {path} to {context}', [
                        'path'    => $request->getPathInfo(),
                        'context' => $context
                    ]);
                } else {
                    $this->logger->debug('Could not resolve a pimcore context for path {path}', [
                        'path' => $request->getPathInfo()
                    ]);
                }
            }
        } else {
            // copy master pimcore context to sub-request if available
            if (!$this->resolver->getPimcoreContext($request)) {
                if ($masterType = $this->resolver->getPimcoreContext($this->requestStack->getMasterRequest())) {
                    $this->resolver->setPimcoreContext($request, $masterType);
                }
            }
        }
    }
}
