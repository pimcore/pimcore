<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\RequestContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestContextListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestContextResolver
     */
    protected $resolver;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestContextResolver $resolver
     * @param RequestStack $requestStack
     */
    public function __construct(RequestContextResolver $resolver, RequestStack $requestStack)
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
            // if there's no request context set on the request, try to guess and set it
            if (!$this->resolver->getRequestContext($request)) {
                $context = $this->resolver->guessRequestContext($request);

                if ($context) {
                    $this->resolver->setRequestContext($request, $context);

                    $this->logger->debug('Resolved request context for path {path} to {context}', [
                        'path'    => $request->getPathInfo(),
                        'context' => $context
                    ]);
                } else {
                    $this->logger->debug('Could not resolve a request context for path {path}', [
                        'path' => $request->getPathInfo()
                    ]);
                }
            }
        } else {
            // copy master request context to sub-request if available
            if (!$this->resolver->getRequestContext($request)) {
                if ($masterType = $this->resolver->getRequestContext($this->requestStack->getMasterRequest())) {
                    $this->resolver->setRequestContext($request, $masterType);
                }
            }
        }
    }
}
