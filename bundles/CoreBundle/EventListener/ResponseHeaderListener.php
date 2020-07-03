<?php

declare(strict_types=1);

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

use Pimcore\Http\Request\Resolver\ResponseHeaderResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseHeaderListener implements EventSubscriberInterface
{
    /**
     * @var ResponseHeaderResolver
     */
    private $responseHeaderResolver;

    /**
     * @param ResponseHeaderResolver $responseHeaderResolver
     */
    public function __construct(ResponseHeaderResolver $responseHeaderResolver)
    {
        $this->responseHeaderResolver = $responseHeaderResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 32],
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $headers = $this->responseHeaderResolver->getResponseHeaders($event->getRequest());

        if (empty($headers)) {
            return;
        }

        $response = $event->getResponse();
        foreach ($headers as $header) {
            $response->headers->set($header->getKey(), $header->getValues(), $header->getReplace());
        }
    }
}
