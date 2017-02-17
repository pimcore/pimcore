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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

class Session
{

    /**
     * contains the session namespace objects
     * @var array
     */
    protected static $sessions = [];

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
    protected static $options = [
        "throw_startup_exceptions" => false,
        "name" => "pimcore_admin_sid",
        "strict" => false,
        "use_trans_sid" => false,
        "use_only_cookies" => false,
        "cookie_httponly" => true
    ];

    /**
     * @var array
     */
    protected static $restoreSession = [];

    /**
     * @param $name
     * @param $value
     */
    public static function setOption($name, $value)
    {
        self::$options[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getOption($name)
    {
        if (isset(self::$options[$name])) {
            return self::$options[$name];
        }

        return null;
    }

    /**
     * @param callable $func
     * @param string $name
     *
     * @return mixed
     */
    public static function useSession($func, $name = "pimcore_admin")
    {
        $bag = static::getSessionBag($name);
        $ret = $func($bag);

        self::writeClose();

        return $ret;
    }

    /**
     * @return object|SymfonySession
     */
    public static function getSession()
    {
        return \Pimcore::getContainer()->get('session');
    }

    /**
     * @param string $name
     * @return SessionBagInterface
     */
    public static function getSessionBag($name)
    {
        $bag = static::getSession()->getBag($name);
        self::$openedSessions++;

        return $bag;
    }

    /**
     * @param string $namespace
     * @param bool $readOnly
     * @return \Zend_Session_Namespace
     * @throws \Zend_Session_Exception
     */
    public static function get($namespace = "pimcore_admin", $readOnly = false)
    {
        throw new \RuntimeException(__METHOD__ . ' is not supported');

        $initSession = !\Zend_Session::isStarted();
        $forceStart = !$readOnly; // we don't force the session to start in read-only mode (default behavior)
        $sName = self::getOption("name");

        if (self::backupForeignSession()) {
            $initSession = true;
            $forceStart = true;
        }

        if ($initSession) {
            \Zend_Session::setOptions(self::$options);
        }

        try {
            try {
                if ($initSession) {
                    // only set the session id if the cookie isn't present, otherwise Set-Cookie is always in the headers
                    if (array_key_exists($sName, $_REQUEST) && !empty($_REQUEST[$sName]) && (!array_key_exists($sName, $_COOKIE) || empty($_COOKIE[$sName]))) {
                        // get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                        \Zend_Session::setId($_REQUEST[$sName]);
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
            \Zend_Session::start();
        }

        if ($forceStart) {
            @session_start();
            self::$sessionCookieCleanupNeeded = true;
        }

        if (!array_key_exists($namespace, self::$sessions) || !self::$sessions[$namespace] instanceof \Zend_Session_Namespace) {
            try {
                self::$sessions[$namespace] = new Session\Container($namespace);
            } catch (\Exception $e) {
                // invalid session, regenerate the session, and return a dummy object
                \Zend_Session::regenerateId();

                return new \stdClass();
            }
        }

        self::$openedSessions++;
        self::$sessions[$namespace]->unlock();

        return self::$sessions[$namespace];
    }

    /**
     * @param string $namespace
     * @return \stdClass
     */
    public static function getReadOnly($namespace = "pimcore_admin")
    {
        throw new \RuntimeException(__METHOD__ . ' is not supported');

        $session = self::get($namespace, true);
        $session->lock();
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

            // session_write_close();
            // self::restoreForeignSession();
        }
    }

    /**
     * @throws \Zend_Session_Exception
     */
    public static function regenerateId()
    {
        static::getSession()->migrate();
        // \Zend_Session::regenerateId();
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
        throw new \RuntimeException(__METHOD__ . ' is not supported');

        $sName = self::getOption("name");
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
        throw new \RuntimeException(__METHOD__ . ' is not supported');

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
