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

namespace Pimcore\Tests\Unit\Cache\FullPage;

use BadMethodCallException;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Regression tests for https://github.com/pimcore/platform-version/issues/172 —
 * SessionStatus::isDisabledBySession() read $_SESSION without ever starting the
 * session, so a full-page-cache HIT (onKernelRequest, before the firewall runs)
 * always saw an empty $_SESSION and served the anonymous cached page to a
 * logged-in visitor, even though a session cookie with real data was present.
 */
class SessionStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = null;

        parent::tearDown();
    }

    public function testCacheHitWithPreviousSessionContainingDataIsDisabled(): void
    {
        $sessionStatus = new SessionStatus('_sf2_meta', $this->createEventDispatcher());

        $session = $this->createLazySession('PHPSESSID', 'abc123', ['cart' => ['item' => 1]]);
        $request = $this->createRequestWithPreviousSession($session, 'PHPSESSID');

        $this->assertTrue(
            $sessionStatus->isDisabledBySession($request),
            'A cache HIT for a request carrying a session cookie with real data must disable ' .
            'the full page cache, not silently serve the anonymous cached response.'
        );
    }

    public function testRequestWithoutPreviousSessionIsNotDisabled(): void
    {
        $sessionStatus = new SessionStatus('_sf2_meta', $this->createEventDispatcher());

        $session = $this->createLazySession('PHPSESSID', 'abc123', ['cart' => ['item' => 1]]);
        $request = Request::create('/');
        $request->setSession($session);

        $this->assertFalse(
            $sessionStatus->isDisabledBySession($request),
            'A request with no session cookie at all must not be penalized - it never had a ' .
            'previous session to start.'
        );
    }

    public function testSessionWithOnlyIgnoredKeysIsNotDisabled(): void
    {
        $sessionStatus = new SessionStatus('_sf2_meta', $this->createEventDispatcher());

        $session = $this->createLazySession('PHPSESSID', 'abc123', ['_sf2_meta' => ['u' => 1]]);
        $request = $this->createRequestWithPreviousSession($session, 'PHPSESSID');

        $this->assertFalse(
            $sessionStatus->isDisabledBySession($request),
            'A session that only carries the ignored metadata namespace must not disable caching.'
        );
    }

    public function testSessionAlreadyStartedIsStillDetected(): void
    {
        $sessionStatus = new SessionStatus('_sf2_meta', $this->createEventDispatcher());

        $session = $this->createLazySession('PHPSESSID', 'abc123', ['user' => 42]);
        $session->start();

        $request = $this->createRequestWithPreviousSession($session, 'PHPSESSID');

        $this->assertTrue(
            $sessionStatus->isDisabledBySession($request),
            'The store-side check (session already started by app code) must keep working.'
        );
    }

    private function createEventDispatcher(): EventDispatcherInterface
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    private function createRequestWithPreviousSession(SessionInterface $session, string $cookieName): Request
    {
        $request = Request::create('/', 'GET', [], [$cookieName => $session->getId()]);
        $request->setSession($session);

        return $request;
    }

    /**
     * A minimal SessionInterface double that mimics PHP's native, lazily-started
     * session: $_SESSION only becomes populated once start() is actually invoked -
     * exactly like AbstractSessionListener, which copies the session id from the
     * cookie onto the Session object without starting it.
     *
     * @param array<string, mixed> $dataOnStart
     */
    private function createLazySession(string $name, string $sessionId, array $dataOnStart): SessionInterface
    {
        return new class($name, $sessionId, $dataOnStart) implements SessionInterface {
            private bool $started = false;

            public function __construct(
                private string $name,
                private string $id,
                private array $dataOnStart
            ) {
            }

            public function start(): bool
            {
                $this->started = true;
                $_SESSION = $this->dataOnStart;

                return true;
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function setId(string $id): void
            {
                $this->id = $id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function setName(string $name): void
            {
                $this->name = $name;
            }

            public function invalidate(?int $lifetime = null): bool
            {
                $_SESSION = [];

                return true;
            }

            public function migrate(bool $destroy = false, ?int $lifetime = null): bool
            {
                return true;
            }

            public function save(): void
            {
            }

            public function has(string $name): bool
            {
                return isset($_SESSION[$name]);
            }

            public function get(string $name, mixed $default = null): mixed
            {
                return $_SESSION[$name] ?? $default;
            }

            public function set(string $name, mixed $value): void
            {
                $_SESSION[$name] = $value;
            }

            public function all(): array
            {
                return $_SESSION ?? [];
            }

            public function replace(array $attributes): void
            {
                $_SESSION = $attributes;
            }

            public function remove(string $name): mixed
            {
                $value = $_SESSION[$name] ?? null;
                unset($_SESSION[$name]);

                return $value;
            }

            public function clear(): void
            {
                $_SESSION = [];
            }

            public function isStarted(): bool
            {
                return $this->started;
            }

            public function registerBag(SessionBagInterface $bag): void
            {
            }

            public function getBag(string $name): SessionBagInterface
            {
                throw new BadMethodCallException(__METHOD__ . ' is not used by SessionStatus.');
            }

            public function getMetadataBag(): MetadataBag
            {
                throw new BadMethodCallException(__METHOD__ . ' is not used by SessionStatus.');
            }
        };
    }
}
