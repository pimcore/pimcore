<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Tool;

class Session {

    /**
     * contains the session namespace objects
     * @var array
     */
    protected static $sessions = array();

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
        "gc_maxlifetime" => 7200,
        "name" => "pimcore_admin_sid",
        "strict" => false,
        "use_trans_sid" => false,
        "use_only_cookies" => false,
        "cookie_httponly" => true
    ];

    /**
     * @param $name
     * @param $value
     */
    public static function setOption($name, $value) {
        self::$options[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getOption($name) {
        if(isset(self::$options[$name])) {
            return self::$options[$name];
        }

        return null;
    }

    /**
     * @static
     * @return void
     */
    public static function initSession() {

        if(!\Zend_Session::isStarted()) {
            \Zend_Session::setOptions(self::$options);
        }

        try {
            try {
                if(!\Zend_Session::isStarted()) {
                    $sName = self::getOption("name");

                    // only set the session id if the cookie isn't present, otherwise Set-Cookie is always in the headers
                    if (array_key_exists($sName, $_REQUEST) && !empty($_REQUEST[$sName]) && (!array_key_exists($sName, $_COOKIE) || empty($_COOKIE[$sName]))) {
                        // get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                        \Zend_Session::setId($_REQUEST[$sName]);
                    }
                }
            }
            catch (\Exception $e) {
                \Logger::error("Problem while starting session");
                \Logger::error($e);
            }
        }
        catch (\Exception $e) {
            \Logger::emergency("there is a problem with admin session");
            die();
        }
    }

    /**
     * @param $func
     * @param string $namespace
     * @return mixed
     */
    public static function useSession($func, $namespace = "pimcore_admin") {

        $ret = $func(self::get($namespace));
        self::writeClose();

        return $ret;
    }

    /**
     * @param string $namespace
     * @param bool $readOnly
     * @return \stdClass
     * @throws \Zend_Session_Exception
     */
    public static function get ($namespace = "pimcore_admin", $readOnly = false) {
        self::initSession();

        if(!\Zend_Session::isStarted()) {
            \Zend_Session::start();
        }

        if(!$readOnly) { // we don't force the session to start in read-only mode
            @session_start();
            self::$sessionCookieCleanupNeeded = true;
        }

        if(!array_key_exists($namespace, self::$sessions) || !self::$sessions[$namespace] instanceof \Zend_Session_Namespace) {
            try {
                self::$sessions[$namespace] = new \Zend_Session_Namespace($namespace);
            } catch (\Exception $e) {
                // invalid session, regenerate the session, and return a dummy object
                \Zend_Session::regenerateId();
                return new \stdClass();
            }
        }

        self::$openedSessions++;

        return self::$sessions[$namespace];
    }

    /**
     * @param string $namespace
     * @return \stdClass
     */
    public static function getReadOnly($namespace = "pimcore_admin") {
        $session = self::get($namespace, true);
        self::writeClose();
        return $session;
    }

    /**
     *
     */
    public static function writeClose() {
        self::$openedSessions--;

        if(!self::$openedSessions) { // do not write session data if there's still an open session
            session_write_close();
        }
    }

    /**
     * @throws \Zend_Session_Exception
     */
    public static function regenerateId() {
        \Zend_Session::regenerateId();
    }

    /**
     * @return bool
     */
    public static function isSessionCookieCleanupNeeded() {
        return self::$sessionCookieCleanupNeeded;
    }
}
