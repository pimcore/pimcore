<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\RequestTypeResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestTypeListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestTypeResolver
     */
    protected $resolver;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestTypeResolver $resolver
     * @param RequestStack $requestStack
     */
    public function __construct(RequestTypeResolver $resolver, RequestStack $requestStack)
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
            // if there's no request type set on the request, try to guess and set it
            if (!$this->resolver->getRequestType($request)) {
                $type = $this->resolver->guessRequestType($request);

                if ($type) {
                    $this->resolver->setRequestType($request, $type);

                    $this->logger->debug('Resolved request type for path {path} to {type}', [
                        'path' => $request->getPathInfo(),
                        'type' => $type
                    ]);
                } else {
                    $this->logger->debug('Could not resolve a request type for path {path}', [
                        'path' => $request->getPathInfo()
                    ]);
                }
            }
        } else {
            // copy master request type to sub-request if available
            if (!$this->resolver->getRequestType($request)) {
                if ($masterType = $this->resolver->getRequestType($this->requestStack->getMasterRequest())) {
                    $this->resolver->setRequestType($request, $masterType);
                }
            }
        }
    }
}
