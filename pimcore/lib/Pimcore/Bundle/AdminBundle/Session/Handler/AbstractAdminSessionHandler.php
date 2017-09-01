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

namespace Pimcore\Bundle\AdminBundle\Session\Handler;

use Pimcore\Session\Attribute\LockableAttributeBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractAdminSessionHandler implements AdminSessionHandlerInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getSessionId()
    {
        return $this->useSession(function (SessionInterface $session) {
            return $session->getId();
        });
    }

    /**
     * @inheritdoc
     */
    public function getSessionName()
    {
        return $this->session->getName();
    }

    /**
     * Loads the session
     *
     * @return SessionInterface
     */
    abstract protected function loadSession(): SessionInterface;

    /**
     * @inheritdoc
     */
    public function useSession(callable $callable)
    {
        $session = $this->loadSession();

        $result = call_user_func_array($callable, [$session]);

        $this->writeClose();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function useSessionAttributeBag(callable $callable, string $name = 'pimcore_admin')
    {
        $session      = $this->loadSession();
        $attributeBag = $this->loadAttributeBag($name, $session);

        $result = call_user_func_array($callable, [$attributeBag, $session]);

        $this->writeClose();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlyAttributeBag(string $name = 'pimcore_admin'): AttributeBagInterface
    {
        $bag = $this->useSessionAttributeBag(function (AttributeBagInterface $bag) {
            if ($bag instanceof LockableAttributeBagInterface) {
                $bag->lock();
            }

            return $bag;
        }, $name);

        return $bag;
    }

    /**
     * @inheritdoc
     */
    public function invalidate(int $lifetime = null): bool
    {
        return $this->session->invalidate($lifetime);
    }

    /**
     * @inheritdoc
     */
    public function regenerateId(): bool
    {
        return $this->useSession(function (SessionInterface $session) {
            return $session->migrate(true);
        });
    }

    /**
     * @inheritdoc
     */
    public function loadAttributeBag(string $name, SessionInterface $session = null): SessionBagInterface
    {
        if (null === $session) {
            $session = $this->loadSession();
        }

        $attributeBag = $session->getBag($name);
        if ($attributeBag instanceof LockableAttributeBagInterface) {
            $attributeBag->unlock();
        }

        return $attributeBag;
    }

    /**
     * @inheritdoc
     */
    public function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool
    {
        $sessionName = $this->getSessionName();
        if (empty($sessionName)) {
            return false;
        }

        $properties = ['cookies'];

        if ($checkRequestParams) {
            $properties[] = 'request';
            $properties[] = 'query';
        }

        foreach ($properties as $property) {
            if ($request->$property->has($sessionName) && !empty($request->$property->get($sessionName))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false): string
    {
        if ($this->requestHasSessionId($request, $checkRequestParams)) {
            $sessionName = $this->getSessionName();

            if ($sessionId = $request->cookies->get($sessionName)) {
                return $sessionId;
            }

            if ($checkRequestParams) {
                if ($sessionId = $request->request->get($sessionName)) {
                    return $sessionId;
                }

                if ($sessionId = $request->query->get($sessionName)) {
                    return $sessionId;
                }
            }
        }

        throw new \RuntimeException('Failed to get session ID from request');
    }
}
