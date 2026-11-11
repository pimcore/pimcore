<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Cache\FullPage;

use Pimcore\Event\Cache\FullPage\IgnoredSessionKeysEvent;
use Pimcore\Event\FullPageCacheEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Determines if the full page cache should be disabled due to
 * session (started session containing data).
 *
 * @internal
 */
class SessionStatus
{
    private string $symfonyMetadataStorageKey;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        string $symfonyMetadataStorageKey,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->symfonyMetadataStorageKey = $symfonyMetadataStorageKey;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function isDisabledBySession(Request $request): bool
    {
        if (!$request->hasSession() || empty($request->getSession()->getId())) {
            return false;
        }

        // On a cache HIT (onKernelRequest), the session is only lazily initialized: the
        // session id has been copied onto the Request from the cookie, but the session
        // itself was never started, so $_SESSION below would always be empty. Start it
        // explicitly whenever the request actually carries a previous session, so data
        // written in an earlier request is visible here too.
        if ($request->hasPreviousSession()) {
            $session = $request->getSession();
            if (!$session->isStarted()) {
                $session->start();
            }
        }

        // we fall back to $_SESSION from here on as the session API does not expose a list of namespaces
        $sessionData = $_SESSION ?? null;
        if (!$sessionData) {
            return false;
        }

        // disable full page cache if any session key besides the ignored
        // ones (e.g. symfony metadata, targeting) have data
        $ignoredSessionKeys = $this->getIgnoredSessionKeys();

        foreach ($sessionData as $key => $value) {
            if (!in_array($key, $ignoredSessionKeys) && !empty($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getIgnoredSessionKeys(): array
    {
        $event = new IgnoredSessionKeysEvent([$this->symfonyMetadataStorageKey]);

        $this->eventDispatcher->dispatch($event, FullPageCacheEvents::IGNORED_SESSION_KEYS);

        return $event->getKeys();
    }
}
