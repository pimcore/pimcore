<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 *
 * @internal
 */
class TargetingElementListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    public function __construct(
        protected DocumentResolver $documentResolver,
        private DocumentTargetingConfigurator $targetingConfigurator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 30], // has to be after DocumentFallbackListener
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($event->isMainRequest()) {
            $request = $event->getRequest();
            if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
                return;
            }

            if ($request->attributes->get('_route') === 'fos_js_routing_js') {
                return;
            }

            $document = $this->documentResolver->getDocument($request);

            if ($document) {
                // apply target group configuration
                $this->applyTargetGroups($request, $document);
                $this->documentResolver->setDocument($request, $document);
            }
        }
    }

    protected function applyTargetGroups(Request $request, Document $document): void
    {
        if (!$document instanceof TargetingDocumentInterface) {
            return;
        }

        if (class_exists(Staticroute::class) && null !== Staticroute::getCurrentRoute()) {
            return;
        }

        // reset because of preview and editmode (saved in session)
        $document->setUseTargetGroup(null);

        $this->targetingConfigurator->configureTargetGroup($document);

        if ($document->getUseTargetGroup()) {
            $this->logger->info('Setting target group to {targetGroup} for document {document}', [
                'targetGroup' => $document->getUseTargetGroup(),
                'document' => $document->getFullPath(),
            ]);
        }
    }
}
