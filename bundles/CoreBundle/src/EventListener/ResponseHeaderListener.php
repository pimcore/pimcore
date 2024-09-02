<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Controller\Attribute\ResponseHeader;
use Pimcore\Http\Request\Resolver\ResponseHeaderResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class ResponseHeaderListener implements EventSubscriberInterface
{
    public function __construct(private ResponseHeaderResolver $responseHeaderResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', 32],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $headers = $this->responseHeaderResolver->getResponseHeaders($event->getRequest());

        if (empty($headers)) {
            return;
        }

        $response = $event->getResponse();
        foreach ($headers as $header) {
            if ($header instanceof ResponseHeader) {
                $response->headers->set($header->getKey(), $header->getValues(), $header->getReplace());
            }
        }
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!is_array($attributes = $event->getAttributes()[ResponseHeader::class] ?? null)) {
            return;
        }

        $responseHeaders = array_merge($this->responseHeaderResolver->getResponseHeaders($request), $attributes);

        $request->attributes->set($this->responseHeaderResolver::ATTRIBUTE_RESPONSE_HEADER, $responseHeaders);
    }
}
