<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Context\Initializer\ContextInitializerInterface;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * @var ContextInitializerInterface
     */
    protected $contextInitializer;

    /**
     * @param PimcoreContextResolver $resolver
     * @param RequestStack $requestStack
     * @param ContextInitializerInterface $contextInitializer
     */
    public function __construct(
        PimcoreContextResolver $resolver,
        RequestStack $requestStack,
        ContextInitializerInterface $contextInitializer
    )
    {
        $this->resolver           = $resolver;
        $this->requestStack       = $requestStack;
        $this->contextInitializer = $contextInitializer;
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
                if ($masterContext = $this->resolver->getPimcoreContext($this->requestStack->getMasterRequest())) {
                    $this->resolver->setPimcoreContext($request, $masterContext);
                }
            }
        }

        $this->initializeContext($request, $event->getRequestType());
    }

    /**
     * Run context specific initializers
     *
     * @param Request $request
     * @param int $requestType
     */
    protected function initializeContext(Request $request, $requestType = KernelInterface::MASTER_REQUEST)
    {
        $context = $this->resolver->getPimcoreContext($request);
        if ($context) {
            $this->contextInitializer->initialize($request, $context, $requestType);
        }
    }
}
