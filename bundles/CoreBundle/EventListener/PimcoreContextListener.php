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

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PimcoreContextListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING = '_pimcore_context_force_resolving';

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
        $this->resolver = $resolver;
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
            KernelEvents::REQUEST => ['onKernelRequest', 24],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($event->isMasterRequest() || $event->getRequest()->attributes->has(self::ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING)) {
            $context = $this->resolver->getPimcoreContext($request);

            if ($context) {
                $this->logger->debug('Resolved pimcore context for path {path} to {context}', [
                    'path' => $request->getPathInfo(),
                    'context' => $context,
                ]);
            } else {
                $this->logger->debug('Could not resolve a pimcore context for path {path}', [
                    'path' => $request->getPathInfo(),
                ]);
            }

            $this->initializeContext($context, $request);
        }
    }

    /**
     * Do context specific initialization
     *
     * @param string $context
     * @param Request $request
     */
    protected function initializeContext($context, $request)
    {
        if ($context == PimcoreContextResolver::CONTEXT_ADMIN || $context == PimcoreContextResolver::CONTEXT_WEBSERVICE) {
            \Pimcore::setAdminMode();
            Document::setHideUnpublished(false);
            DataObject\AbstractObject::setHideUnpublished(false);

            if ($context == PimcoreContextResolver::CONTEXT_WEBSERVICE) {
                DataObject\AbstractObject::setGetInheritedValues(filter_var($request->get('inheritance'), FILTER_VALIDATE_BOOLEAN));
            }
            DataObject\Localizedfield::setGetFallbackValues(false);
        } else {
            \Pimcore::unsetAdminMode();
            Document::setHideUnpublished(true);
            DataObject\AbstractObject::setHideUnpublished(true);
            DataObject\AbstractObject::setGetInheritedValues(true);
            DataObject\Localizedfield::setGetFallbackValues(true);
        }
    }
}
