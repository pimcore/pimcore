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

final class Session
{
    private static ?AdminSessionHandlerInterface $handler = null;

    public static function getHandler(): AdminSessionHandlerInterface
    {
        if (null === static::$handler) {
            static::$handler = \Pimcore::getContainer()->get(AdminSessionHandler::class);
        }

        return static::$handler;
    }

    public static function setHandler(AdminSessionHandlerInterface $handler): void
    {
        static::$handler = $handler;
    }

    public static function useSession(callable $func, string $namespace = 'pimcore_admin'): mixed
    {
        return static::getHandler()->useSessionAttributeBag($func, $namespace);
    }

    public static function getSessionId(): string
    {
        return static::getHandler()->getSessionId();
    }

    public static function getSessionName(): string
    {
        return static::getHandler()->getSessionName();
    }

    public static function invalidate(): bool
    {
        return static::getHandler()->invalidate();
    }

    public static function regenerateId(): bool
    {
        return static::getHandler()->regenerateId();
    }

    public static function requestHasSessionId(Request $request, bool $checkRequestParams = false): bool
    {
        return static::getHandler()->requestHasSessionId($request, $checkRequestParams);
    }

    public static function getSessionIdFromRequest(Request $request, bool $checkRequestParams = false): string
    {
        return static::getHandler()->getSessionIdFromRequest($request, $checkRequestParams);
    }

    /**
     * Start session and get an attribute bag
     *
     * @param string $namespace
     *
     * @return AttributeBagInterface|null
     */
    public static function get(string $namespace = 'pimcore_admin'): ?AttributeBagInterface
    {
        $bag = static::getHandler()->loadAttributeBag($namespace);
        if ($bag instanceof AttributeBagInterface) {
            return $bag;
        }

        return null;
    }

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
