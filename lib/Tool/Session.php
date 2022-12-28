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
    /**
     * @var AdminSessionHandlerInterface
     */
    private static $handler;

    /**
     * @desc This is for forward compatibility, the custom Session implementation is not being used anymore in Pimcore 11.
     * @desc For forward compatibility, you can use this class and pass the SessionInterface from the request, in Pimcore 10.6, the Admin Session will be used instead
     *
     * @param SessionInterface $session Session being used is the Admin Session, you can safely pass nothing here
     *
     * @param callable(AttributeBagInterface, SessionInterface):mixed $func
     *
     */
    public static function useBag(SessionInterface $session, callable $func, string $namespace = 'pimcore_admin'): mixed
    {
        return static::useSession($func, $namespace);
    }

    public static function getSessionBag(
        SessionInterface $session,
        string $namespace = 'pimcore_admin'
    ): ?AttributeBagInterface {
        return static::get($namespace);
    }

    /**
     * @deprecated
     */
    public static function getHandler(): AdminSessionHandlerInterface
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::getHandler tag is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.');

        if (null === static::$handler) {
            static::$handler = \Pimcore::getContainer()->get(AdminSessionHandler::class);
        }

        return static::$handler;
    }

    public static function setHandler(AdminSessionHandlerInterface $handler)
    {
        static::$handler = $handler;
    }

    /**
     * @param callable $func
     * @param string $namespace
     *
     * @return mixed
     *
     * @deprecated
     */
    public static function useSession($func, string $namespace = 'pimcore_admin')
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::useSession tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use \Pimcore\Tool\Session::useBag instead.');

        return static::getHandler()->useSessionAttributeBag($func, $namespace);
    }

    /**
     * @return string
     *
     * @deprecated
     */
    public static function getSessionId()
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::getSessionId tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionId instead.');

        return static::getHandler()->getSessionId();
    }

    /**
     * @return string
     *
     * @deprecated
     */
    public static function getSessionName()
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::getSessionName tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionName instead.');

        return static::getHandler()->getSessionName();
    }

    /**
     * @return bool
     *
     * @deprecated
     */
    public static function invalidate(): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::invalidate tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request invalidate instead.');

        return static::getHandler()->invalidate();
    }

    /**
     * @return bool
     *
     * @deprecated
     */
    public static function regenerateId(): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::regenerateId tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request migrate instead.');

        return static::getHandler()->regenerateId();
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return bool
     *
     * @deprecated
     */
    public static function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::requestHasSessionId tag is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given, use Request Session instead.');

        return static::getHandler()->requestHasSessionId($request, $checkRequestParams);
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return string
     *
     * @deprecated
     */
    public static function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false)
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::getSessionIdFromRequest tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use the Request SessionId instead.');

        return static::getHandler()->getSessionIdFromRequest($request, $checkRequestParams);
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
    public static function get(string $namespace = 'pimcore_admin')
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::get tag is deprecated since version 10.6 and will be removed in Pimcore 11. Use \Pimcore\Tool\Session::getSessionBag instead.');

        $bag = static::getHandler()->loadAttributeBag($namespace);
        if ($bag instanceof AttributeBagInterface) {
            return $bag;
        }

        return null;
    }

    /**
     * @param string $namespace
     *
     * @return AttributeBagInterface
     *
     * @deprecated
     */
    public static function getReadOnly(string $namespace = 'pimcore_admin'): AttributeBagInterface
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::getReadOnly tag is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.');

        return static::getHandler()->getReadOnlyAttributeBag($namespace);
    }

    /**
     * Saves the session if it is the last admin session which was open
     *
     * @deprecated
     */
    public static function writeClose()
    {
        trigger_deprecation('pimcore/pimcore', '10.6', 'Usage of \Pimcore\Tool\Session::writeClose tag is deprecated since version 10.6 and will be removed in Pimcore 11. No alternative given.');

        return static::getHandler()->writeClose();
    }
}
