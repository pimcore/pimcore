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

namespace Pimcore\Targeting\Session;

use Pimcore\Event\Cache\FullPage\IgnoredSessionKeysEvent;
use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\FullPageCacheEvents;
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionConfigurator implements SessionConfiguratorInterface, EventSubscriberInterface
{
    const TARGETING_BAG_SESSION = 'pimcore_targeting_session';
    const TARGETING_BAG_VISITOR = 'pimcore_targeting_visitor';

    public static function getSubscribedEvents()
    {
        return [
            FullPageCacheEvents::IGNORED_SESSION_KEYS => 'configureIgnoredSessionKeys',
            FullPageCacheEvents::PREPARE_RESPONSE => 'prepareFullPageCacheResponse',
        ];
    }

    public function configure(SessionInterface $session)
    {
        $sessionBag = new NamespacedAttributeBag('_' . self::TARGETING_BAG_SESSION);
        $sessionBag->setName(self::TARGETING_BAG_SESSION);

        $visitorBag = new NamespacedAttributeBag('_' . self::TARGETING_BAG_VISITOR);
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

        /** @var Cookie $cookie */
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
