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

use Pimcore\Session\Attribute\LockableAttributeBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AdminSessionHandlerInterface
{
    /**
     * @return string
     */
    public function getSessionName();

    /**
     * Returns the session ID
     *
     * @see SessionInterface::getId()
     *
     * @return string
     */
    public function getSessionId();

    /**
     * Use the admin session and immediately close it after usage. The callable gets the session as its first argument.
     *
     * @param callable $callable
     *
     * @return mixed Callable result
     */
    public function useSession(callable $callable);

    /**
     * Use an attribute bag and close the session immediately after usage. The callable gets the attribute bag and the
     * session as arguments.
     *
     * @param callable $callable
     * @param string $name
     *
     * @return mixed
     */
    public function useSessionAttributeBag(callable $callable, string $name = 'pimcore_admin');

    /**
     * Loads an attribute bag, optionally lock it if supported and close the session.
     *
     * @param string $name
     *
     * @return AttributeBagInterface
     */
    public function getReadOnlyAttributeBag(string $name = 'pimcore_admin'): AttributeBagInterface;

    /**
     * @param int|null $lifetime
     *
     * @return bool
     */
    public function invalidate(int $lifetime = null): bool;

    /**
     * Regenerates the session ID
     *
     * @see SessionInterface::migrate()
     *
     * @return bool
     */
    public function regenerateId(): bool;

    /**
     * Directly loads an attribute bag from the session. You should call writeClose() after usage
     * to make sure the admin session is written and foreign sessions are restored.
     *
     * @param string $name
     * @param SessionInterface|null $session
     *
     * @return AttributeBagInterface|LockableAttributeBag|SessionBagInterface
     */
    public function loadAttributeBag(string $name, SessionInterface $session = null): SessionBagInterface;

    /**
     * Saves the session if it is the last admin session which was opened
     */
    public function writeClose();

    /**
     * Check if the request has a cookie or a param matching the session name.
     *
     * @param Request $request
     * @param bool $checkRequestParams
     *
     * @return bool
     */
    public function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool;

    /**
     * Get session ID from request cookie/param
     *
     * @param Request $request
     * @param bool $checkRequestParams
     *
     * @return string
     */
    public function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false): string;
}
