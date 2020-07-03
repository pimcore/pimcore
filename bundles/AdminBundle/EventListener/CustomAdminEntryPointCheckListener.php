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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CustomAdminEntryPointCheckListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected $customAdminPathIdentifier;

    public function __construct(?string $customAdminPathIdentifier)
    {
        $this->customAdminPathIdentifier = $customAdminPathIdentifier;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 560],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($event->isMasterRequest() && $this->customAdminPathIdentifier && $this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            if ($this->customAdminPathIdentifier !== $request->cookies->get('pimcore_custom_admin')) {
                // display standard 404 error page, we don't expose that /admin exists but access is prohibited
                throw new NotFoundHttpException();
            }
        }
    }
}
