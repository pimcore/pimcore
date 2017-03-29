<?php
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

use Pimcore\Session\AdminSessionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Session
{
    /**
     * @var AdminSessionHandler
     */
    private static $handler;

    /**
     * @return AdminSessionHandler
     */
    public static function getHandler()
    {
        if (null === static::$handler) {
            static::$handler = \Pimcore::getContainer()->get('pimcore_admin.session.handler');
        }

        return static::$handler;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getOption($name)
    {
        return static::getHandler()->getOption($name);
    }

    /**
     * @param $func
     * @param string $namespace
     * @return mixed
     */
    public static function useSession($func, $namespace = "pimcore_admin")
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
    public static function invalidate()
    {
        return static::getHandler()->invalidate();
    }

    /**
     * @return mixed
     */
    public static function regenerateId()
    {
        return static::getHandler()->regenerateId();
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return bool
     */
    public static function requestHasSessionId(Request $request, $checkRequestParams = false)
    {
        return static::getHandler()->requestHasSessionId($request, $checkRequestParams);
    }

    /**
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return string
     */
    public static function getSessionIdFromRequest(Request $request, $checkRequestParams = false)
    {
        return static::getHandler()->getSessionIdFromRequest($request, $checkRequestParams);
    }

    /**
     * Start session and get an attribute bag
     *
     * @param string $namespace
     * @return AttributeBagInterface
     */
    public static function get($namespace = "pimcore_admin")
    {
        return static::getHandler()->loadAttributeBag($namespace);
    }

    /**
     * @param string $namespace
     * @return AttributeBagInterface
     */
    public static function getReadOnly($namespace = "pimcore_admin")
    {
        return static::getHandler()->getReadOnlyAttributeBag($namespace);
    }

    public static function writeClose()
    {
        return static::getHandler()->writeClose();
    }
}
