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

namespace Pimcore\Tool;

use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandler;
use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class Session
{
    private static ?AdminSessionHandlerInterface $handler = null;

    /**
     * @desc This is for forward compatibility, the custom Session implementation is not being used anymore in Pimcore 11.
     * @desc For forward compatibility, you can use this class and pass the SessionInterface from the request, in Pimcore 10.6, the Admin Session will be used instead
     *
     * @param SessionInterface $session Parameter is not used here since the dedicated Admin Session is used. Please pass the Request SessionInterface here for forward compatibility
     * @param callable(AttributeBagInterface, SessionInterface):mixed $func
     *
     */
    public static function useBag(SessionInterface $session, callable $func, string $namespace = 'pimcore_admin'): mixed
    {
        return self::getSessionHandler()->useSessionAttributeBag($func, $namespace);
    }

    /**
     * @desc This is for forward compatibility, the custom Session implementation is not being used anymore in Pimcore 11.
     * @desc For forward compatibility, you can use this class and pass the SessionInterface from the request, in Pimcore 10.6, the Admin Session will be used instead
     *
     * @param SessionInterface $session Parameter is not used here since the dedicated Admin Session is used. Please pass the Request SessionInterface here for forward compatibility
     * @param string $namespace
     *
     */
    public static function getSessionBag(
        SessionInterface $session,
        string $namespace = 'pimcore_admin'
    ): ?AttributeBagInterface {
        $bag = self::getSessionHandler()->loadAttributeBag($namespace);
        if ($bag instanceof AttributeBagInterface) {
            return $bag;
        }

        return null;
    }

    /**
     * @deprecated
     */
    private static function getSessionHandler(): AdminSessionHandlerInterface
    {
        if (null === self::$handler) {
            self::$handler = \Pimcore::getContainer()->get(AdminSessionHandler::class);
        }

        return self::$handler;
    }

    /**
     * @deprecated
     */
    public static function getHandler(): AdminSessionHandlerInterface
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.', __METHOD__));

        return self::getSessionHandler();
    }

    public static function setHandler(AdminSessionHandlerInterface $handler): void

    /**
     * @deprecated
     */
    public static function setHandler(AdminSessionHandlerInterface $handler): void
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.', __METHOD__));

        self::$handler = $handler;
    }

    /**
     * @deprecated
     */
    public static function useSession(callable $func, string $namespace = 'pimcore_admin'): mixed
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use \Pimcore\Tool\Session::useBag instead.', __METHOD__));

        return self::getSessionHandler()->useSessionAttributeBag($func, $namespace);
    }

    /**
     * @deprecated
     */
    public static function getSessionId(): string
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionId instead.', __METHOD__));

        return self::getSessionHandler()->getSessionId();
    }

    /**
     * @deprecated
     */
    public static function getSessionName(): string
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionName instead.', __METHOD__));

        return self::getSessionHandler()->getSessionName();
    }

    /**
     * @deprecated
     */
    public static function invalidate(): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request invalidate instead.', __METHOD__));

        return self::getSessionHandler()->invalidate();
    }

    /**
     * @deprecated
     */
    public static function regenerateId(): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request migrate instead.', __METHOD__));

        return self::getSessionHandler()->regenerateId();
    }

    /**
     * @deprecated
     */
    public static function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given, use Request Session instead.', __METHOD__));

        return self::getSessionHandler()->requestHasSessionId($request, $checkRequestParams);
    }

    /**
     * @deprecated
     */
    public static function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false): string
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionId instead.', __METHOD__));

        return self::getSessionHandler()->getSessionIdFromRequest($request, $checkRequestParams);
    }

    /**
     * Start session and get an attribute bag
     *
     * @param string $namespace
     *
     * @return AttributeBagInterface|null
     *
     * @deprecated
     */
    public static function get(string $namespace = 'pimcore_admin'): ?AttributeBagInterface
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. Use \Pimcore\Tool\Session::getSessionBag instead.', __METHOD__));

        $bag = self::getSessionHandler()->loadAttributeBag($namespace);
        if ($bag instanceof AttributeBagInterface) {
            return $bag;
        }

        return null;
    }

    /**
     * @deprecated
     */
    public static function getReadOnly(string $namespace = 'pimcore_admin'): AttributeBagInterface
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.', __METHOD__));

        return self::getSessionHandler()->getReadOnlyAttributeBag($namespace);
    }

    /**
     * Saves the session if it is the last admin session which was open
     *
     * @deprecated
     */
    public static function writeClose()
    {
        trigger_deprecation('pimcore/pimcore', '10.6', sprintf('Usage of method %s is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.', __METHOD__));

        return self::getSessionHandler()->writeClose();
    }
}
