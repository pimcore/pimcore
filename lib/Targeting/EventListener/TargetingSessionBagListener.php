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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Event\Cache\FullPage\IgnoredSessionKeysEvent;
use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\FullPageCacheEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TargetingSessionBagListener implements EventSubscriberInterface
{
    const TARGETING_BAG_SESSION = 'pimcore_targeting_session';

    const TARGETING_BAG_VISITOR = 'pimcore_targeting_visitor';

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()//: array
    {
        return [
            FullPageCacheEvents::IGNORED_SESSION_KEYS => 'configureIgnoredSessionKeys',
            FullPageCacheEvents::PREPARE_RESPONSE => 'prepareFullPageCacheResponse',
            //run after Symfony\Component\HttpKernel\EventListener\SessionListener
            KernelEvents::REQUEST => ['onKernelRequest', 127],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $session = $event->getRequest()->getSession();

        $sessionBag = new AttributeBag('_' . self::TARGETING_BAG_SESSION);
        $sessionBag->setName(self::TARGETING_BAG_SESSION);

        $visitorBag = new AttributeBag('_' . self::TARGETING_BAG_VISITOR);
        $visitorBag->setName(self::TARGETING_BAG_VISITOR);

        $session->registerBag($sessionBag);
        $session->registerBag($visitorBag);
    }

    public function configureIgnoredSessionKeys(IgnoredSessionKeysEvent $event)
    {
        // configures full page cache to ignore session data in targeting storage
        $event->setKeys(array_merge($event->getKeys(), [
            '_' . self::TARGETING_BAG_SESSION,
            '_' . self::TARGETING_BAG_VISITOR,
        ]));
    }

    /**
     * Removes session cookie from cached response
     *
     * @param PrepareResponseEvent $event
     */
    public function prepareFullPageCacheResponse(PrepareResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->hasSession()) {
            return;
        }

        $sessionName = $request->getSession()->getName();
        if (empty($sessionName)) {
            return;
        }

        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $sessionName) {
                $response->headers->removeCookie(
                    $cookie->getName(),
                    $cookie->getPath(),
                    $cookie->getDomain()
                );
            }
        }
    }
}
