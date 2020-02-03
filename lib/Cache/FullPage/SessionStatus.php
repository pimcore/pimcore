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

namespace Pimcore\Cache\FullPage;

use Pimcore\Event\Cache\FullPage\IgnoredSessionKeysEvent;
use Pimcore\Event\FullPageCacheEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines if the full page cache should be disabled due to
 * session (started session containing data).
 */
class SessionStatus
{
    /**
     * @var string
     */
    private $symfonyMetadataStorageKey;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

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

        // we fall back to $_SESSION from here on as the session API does not expose a list of namespaces
        $sessionData = $_SESSION ?? null;
        if (empty($sessionData)) {
            return false;
        }

        if (!is_array($sessionData)) {
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

    private function getIgnoredSessionKeys(): array
    {
        $event = new IgnoredSessionKeysEvent([$this->symfonyMetadataStorageKey]);

        $this->eventDispatcher->dispatch(FullPageCacheEvents::IGNORED_SESSION_KEYS, $event);

        return $event->getKeys();
    }
}
