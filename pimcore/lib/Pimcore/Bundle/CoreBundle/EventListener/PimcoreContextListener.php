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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Service\Request\PimcoreContextResolver;
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
    public function __construct(
        PimcoreContextResolver $resolver,
        RequestStack $requestStack
    ) {
        $this->resolver           = $resolver;
        $this->requestStack       = $requestStack;
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
            $context = $this->resolver->getPimcoreContext($request);

            if ($context) {
                $this->logger->debug('Resolved pimcore context for path {path} to {context}', [
                    'path'    => $request->getPathInfo(),
                    'context' => $context
                ]);
            } else {
                $this->logger->debug('Could not resolve a pimcore context for path {path}', [
                    'path' => $request->getPathInfo()
                ]);
            }

            $this->initializeContext($context);
        }
    }

    /**
     * Do context specific initialization
     *
     * @param $context
     */
    protected function initializeContext($context)
    {
        if ($context == PimcoreContextResolver::CONTEXT_ADMIN) {
            \Pimcore::setAdminMode();
            Document::setHideUnpublished(false);
            Object\AbstractObject::setHideUnpublished(false);
            Object\AbstractObject::setGetInheritedValues(false);
            Object\Localizedfield::setGetFallbackValues(false);
        } else {
            \Pimcore::unsetAdminMode();
            Document::setHideUnpublished(true);
            Object\AbstractObject::setHideUnpublished(true);
            Object\AbstractObject::setGetInheritedValues(true);
            Object\Localizedfield::setGetFallbackValues(true);
        }
    }
}
