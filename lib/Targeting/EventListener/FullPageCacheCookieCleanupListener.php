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

namespace Pimcore\Targeting\EventListener;

use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\FullPageCacheEvents;
use Pimcore\Targeting\Storage\CookieStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Removes cookie storage cookies from cached response (only from the response object, not
 * from the client's browser).
 */
class FullPageCacheCookieCleanupListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FullPageCacheEvents::PREPARE_RESPONSE => 'onPrepareFullPageCacheResponse',
        ];
    }

    public function onPrepareFullPageCacheResponse(PrepareResponseEvent $event)
    {
        $response = $event->getResponse();
        $cookies = $response->headers->getCookies();

        $blacklist = [
            CookieStorage::COOKIE_NAME_VISITOR,
            CookieStorage::COOKIE_NAME_SESSION,
        ];

        /** @var Cookie $cookie */
        foreach ($cookies as $cookie) {
            if (in_array($cookie->getName(), $blacklist)) {
                $response->headers->removeCookie(
                    $cookie->getName(),
                    $cookie->getPath(),
                    $cookie->getDomain()
                );
            }
        }
    }
}
