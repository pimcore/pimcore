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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Session {

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
     * @static
     * @return void
     */
    public static function initSession() {

        if(!Zend_Session::isStarted()) {
            Zend_Session::setOptions(array(
                "throw_startup_exceptions" => false,
                "gc_maxlifetime" => 7200,
                "name" => "pimcore_admin_sid",
                "strict" => false,
                "use_trans_sid" => false,
                "use_only_cookies" => false
            ));
        }

        try {
            try {
                if(!Zend_Session::isStarted()) {
                    $sName = Zend_Session::getOptions("name");

                    // only set the session id if the cookie isn't present, otherwise Set-Cookie is always in the headers
                    if (array_key_exists($sName, $_REQUEST) && !empty($_REQUEST[$sName]) && (!array_key_exists($sName, $_COOKIE) || empty($_COOKIE[$sName]))) {
                        // get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                        Zend_Session::setId($_REQUEST[$sName]);
                    }
                }
            }
            catch (Exception $e) {
                Logger::error("Problem while starting session");
                Logger::error($e);
            }
        }
        catch (Exception $e) {
            Logger::emergency("there is a problem with admin session");
            die();
        }
    }

    public static function useSession($func, $namespace = "pimcore_admin") {

        self::initSession();

        $ret = $func(self::get($namespace));

        self::writeClose();

        return $ret;
    }

    public static function get ($namespace = "pimcore_admin", $readOnly = false) {
        self::initSession();

        if(!Zend_Session::isStarted()) {
            Zend_Session::start();
        }

        if(!$readOnly) { // we don't force the session to start in read-only mode
            @session_start();
        }

        if(!array_key_exists($namespace, self::$sessions) || !self::$sessions[$namespace] instanceof Zend_Session_Namespace) {
            try {
                self::$sessions[$namespace] = new Zend_Session_Namespace($namespace);
            } catch (\Exception $e) {
                // invalid session, regenerate the session, and return a dummy object
                Zend_Session::regenerateId();
                return new stdClass();
            }
        }

        self::$openedSessions++;

        return self::$sessions[$namespace];
    }

    public static function getReadOnly($namespace = "pimcore_admin") {
        $session = self::get($namespace, true);
        self::writeClose();
        return $session;
    }

    public static function writeClose() {
        self::$openedSessions--;

        if(!self::$openedSessions) { // do not write session data if there's still an open session
            session_write_close();
        }
    }

}
