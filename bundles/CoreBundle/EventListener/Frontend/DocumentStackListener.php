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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Document\DocumentStack;
use Pimcore\Model\Document;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class DocumentStackListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $documents;
    protected $documentStack;

    public function __construct(DocumentStack $documentStack)
    {
        $this->documentStack = $documentStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ($currentDoc = $request->get(DynamicRouter::CONTENT_KEY)) {
            if ($currentDoc instanceof Document) {
                $this->documentStack->push($currentDoc);
            }
        }
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($currentDoc = $request->get(DynamicRouter::CONTENT_KEY)) {
            if ($currentDoc instanceof Document) {
                $this->documentStack->pop();
            }
        }
    }
}
