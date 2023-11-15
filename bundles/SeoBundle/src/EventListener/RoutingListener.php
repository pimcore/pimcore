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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RoutingListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(protected RedirectHandler $redirectHandler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run with high priority as we need to set the site early
            KernelEvents::REQUEST => ['onKernelRequest', 256],

            // run with high priority before handling real errors
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $request = $event->getRequest();
        $response = $this->redirectHandler->checkForRedirect($request, true);
        if ($response) {
            $event->setResponse($response);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        // in case routing didn't find a matching route, check for redirects without override
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            $response = $this->redirectHandler->checkForRedirect($event->getRequest(), false);
            if ($response) {
                $event->setResponse($response);
            }
        }
    }
}
