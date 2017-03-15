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

use Pimcore\Bundle\PimcoreBundle\Session\Attribute\LockableAttributeBagInterface;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Session
{
    /**
     * contains how many sessions are currently open, this is important, because writeClose() must not be called if
     * there is still an open session, this is especially important if something doesn't use the method use() but get()
     * so the session isn't closed automatically after the action is done
     */
    protected static $openedSessions = 0;

    /**
     * when using mod_php, session_start() always adds an Set-Cookie header when called,
     * this is the case in self::get(), so depending on how often self::get() is called the more
     * header will get added to the response, so we clean them up in Pimcore::outputBufferEnd()
     * to avoid problems with (reverse-)proxies such as Varnish who do not like too much Set-Cookie headers
     * @var bool
     */
    protected static $sessionCookieCleanupNeeded = false;

    /**
     * @var array
     */
    protected static $restoreSession = [];

    /**
     * @param $name
     * @return mixed
     */
    public static function getOption($name)
    {
        return static::getSessionStorageFactory()->getOption($name);
    }

    /**
     * @param $func
     * @param string $namespace
     * @return mixed
     */
    public static function useSession($func, $namespace = "pimcore_admin")
    {
        $ret = $func(self::get($namespace));
        self::writeClose();

        return $ret;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public static function getSession()
    {
        return \Pimcore::getContainer()->get('pimcore_admin.session');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage
     */
    public static function getSessionStorage()
    {
        return \Pimcore::getContainer()->get('pimcore_admin.session.storage');
    }

    /**
     * @return \Pimcore\Bundle\PimcoreAdminBundle\Session\AdminSessionStorageFactory
     */
    public static function getSessionStorageFactory()
    {
        return \Pimcore::getContainer()->get('pimcore_admin.session.storage_factory');
    }

    /**
     * @return string
     */
    public static function getSessionName()
    {
        return static::getSessionStorageFactory()->getOption('name');
    }

    /**
     * @param Request $request
     * @param bool    $checkRequest
     *
     * @return bool
     */
    public static function requestHasSessionId(Request $request, $checkRequest = false)
    {
        $sessionName = static::getSessionName();

        $cookieResult = $request->cookies->has($sessionName);
        if ($cookieResult) {
            return true;
        }

        if ($checkRequest) {
            $requestResult = $request->request->has($sessionName) || $request->query->has($sessionName);
            if ($requestResult) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @param bool    $checkRequest
     *
     * @return string
     */
    public static function getSessionIdFromRequest(Request $request, $checkRequest = false)
    {
        if (static::requestHasSessionId($request, $checkRequest)) {
            $sessionName = static::getSessionName();

            if ($sessionId = $request->cookies->get($sessionName)) {
                return $sessionId;
            }

            if ($checkRequest) {
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

    /**
     * Start session and get an attribute bag
     *
     * @param string $namespace
     * @param bool $readOnly
     * @return AttributeBagInterface
     */
    public static function get($namespace = "pimcore_admin", $readOnly = false)
    {
        $session = static::getSession();
        $storage = static::getSessionStorage();
        $factory = static::getSessionStorageFactory();

        $initSession = !$session->isStarted();
        $forceStart = !$readOnly; // we don't force the session to start in read-only mode (default behavior)
        $sName = $factory->getOption('name');

        if (self::backupForeignSession()) {
            $initSession = true;
            $forceStart = true;
        }

        if ($initSession) {
            $factory->initializeStorage($storage);
        }

        try {
            try {
                if ($initSession) {
                    // only set the session id if the cookie isn't present, otherwise Set-Cookie is always in the headers
                    if (array_key_exists($sName, $_REQUEST) && !empty($_REQUEST[$sName]) && (!array_key_exists($sName, $_COOKIE) || empty($_COOKIE[$sName]))) {
                        // get session work with session-id via get (since SwfUpload doesn't support cookies)
                        $session->setId($_REQUEST[$sName]);
                    }
                }
            } catch (\Exception $e) {
                Logger::error("Problem while starting session");
                Logger::error($e);
            }
        } catch (\Exception $e) {
            Logger::emergency("there is a problem with admin session");
            die();
        }

        if ($initSession) {
            $session->start();
        }

        if ($forceStart) {
            @session_start();
            self::$sessionCookieCleanupNeeded = true;
        }

        // TODO handle exceptions and migrate session here as before?
        $attributeBag = $session->getBag($namespace);
        if ($attributeBag instanceof LockableAttributeBagInterface) {
            $attributeBag->unlock();
        }

        /*
        if (!array_key_exists($namespace, self::$sessions) || !self::$sessions[$namespace] instanceof \Zend_Session_Namespace) {
            try {
                self::$sessions[$namespace] = new Session\Container($namespace);
            } catch (\Exception $e) {
                // invalid session, regenerate the session, and return a dummy object
                $session->migrate(true);

                return new \stdClass();
            }
        }
        */

        self::$openedSessions++;

        return $attributeBag;
    }

    /**
     * @param string $namespace
     * @return AttributeBagInterface
     */
    public static function getReadOnly($namespace = "pimcore_admin")
    {
        $session = self::get($namespace, true);

        if ($session instanceof LockableAttributeBagInterface) {
            $session->lock();
        }

        self::writeClose();

        return $session;
    }

    /**
     *
     */
    public static function writeClose()
    {
        self::$openedSessions--;

        if (!self::$openedSessions) { // do not write session data if there's still an open session
            static::getSession()->save();
            self::restoreForeignSession();
        }
    }

    public static function regenerateId()
    {
        static::getSession()->migrate(true);
    }

    /**
     * @return bool
     */
    public static function isSessionCookieCleanupNeeded()
    {
        return self::$sessionCookieCleanupNeeded;
    }

    /**
     * @return bool
     */
    protected static function backupForeignSession()
    {
        $sName = static::getSessionStorageFactory()->getOption('name');
        if ($sName != session_name()) {
            // there's a different session in use, stop it and restart the admin session
            self::$restoreSession = [
                "name" => session_name(),
                "id" => session_id()
            ];

            if (session_id()) {
                @session_write_close();
            }

            if (isset($_COOKIE[$sName])) {
                session_id($_COOKIE[$sName]);
            }

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function restoreForeignSession()
    {
        if (!empty(self::$restoreSession)) {
            session_write_close();

            session_name(self::$restoreSession["name"]);

            if (isset(self::$restoreSession["id"]) && !empty(self::$restoreSession["id"])) {
                session_id(self::$restoreSession["id"]);
                @session_start();
            }

            return true;
        }

        return false;
    }
}
