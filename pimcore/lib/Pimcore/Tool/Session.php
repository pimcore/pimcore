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

namespace Pimcore\Tool;

use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandler;
use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Session
{
    /**
     * @var AdminSessionHandlerInterface
     */
    private static $handler;

    public static function getHandler(): AdminSessionHandlerInterface
    {
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
     * @param $func
     * @param string $namespace
     *
     * @return mixed
     */
    public static function useSession($func, string $namespace = 'pimcore_admin')
    {
        return static::getHandler()->useSessionAttributeBag($func, $namespace);
    }

    /**
     * @return string
     */
    public static function getSessionId()
    {
        return static::getHandler()->getSessionId();
    }

    /**
     * @return string
     */
    public static function getSessionName()
    {
        return static::getHandler()->getSessionName();
    }

    /**
     * @return bool
     */
    public static function invalidate(): bool
    {
        return static::getHandler()->invalidate();
    }

    /**
     * @return bool
     */
    public static function regenerateId(): bool
    {
        return static::getHandler()->regenerateId();
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return bool
     */
    public static function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool
    {
        return static::getHandler()->requestHasSessionId($request, $checkRequestParams);
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return string
     */
    public static function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false)
    {
        return static::getHandler()->getSessionIdFromRequest($request, $checkRequestParams);
    }

    /**
     * Start session and get an attribute bag
     *
     * @param string $namespace
     *
     * @return AttributeBagInterface
     */
    public static function get(string $namespace = 'pimcore_admin'): AttributeBagInterface
    {
        return static::getHandler()->loadAttributeBag($namespace);
    }

    /**
     * @param string $namespace
     *
     * @return AttributeBagInterface
     */
    public static function getReadOnly(string $namespace = 'pimcore_admin'): AttributeBagInterface
    {
        return static::getHandler()->getReadOnlyAttributeBag($namespace);
    }

    /**
     * Saves the session if it is the last admin session which was opene
     */
    public static function writeClose()
    {
        return static::getHandler()->writeClose();
    }
}
