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

use Pimcore\Model\User;

// PHP 5.5 Crypt-API password compatibility layer for PHP version < PHP 5.5
include_once("password_compatibility.php");

class Authentication {

    /**
     * @param $username
     * @param $password
     * @return null|User
     */
    public static function authenticatePlaintext($username, $password) {
        $user = User::getByName($username);

        // user needs to be active, needs a password and an ID (do not allow system user to login, ...)
        if(self::isValidUser($user)) {
            if(self::verifyPassword($user, $password)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @static
     * @throws Exception
     * @return User
     */
    public static function authenticateSession () {

        if(!isset($_COOKIE["pimcore_admin_sid"]) && !isset($_REQUEST["pimcore_admin_sid"])) {
            // if no session cookie / ID no authentication possible, we don't need to start a session
            return null;
        }

        $session = Session::getReadOnly();
        $user = $session->user;
        if ($user instanceof User) {
            // renew user
            $user = User::getById($user->getId());

            if(self::isValidUser($user)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @static
     * @throws Exception
     * @return User
     */
    public static function authenticateHttpBasic () {

        $auth = new \Sabre\HTTP\BasicAuth();
        $auth->setRealm("pimcore");
        $result = $auth->getUserPass();

        if(is_array($result)) {
            list($username, $password) = $result;
            return self::authenticatePlaintext($username, $password);
        }

        $auth->requireLogin();
        \Logger::error("Authentication Basic (WebDAV) required");
        echo "Authentication required\n";
        die();
    }

    /**
     * @param $username
     * @param $token
     * @param bool $adminRequired
     * @return null|User
     */
    public static function authenticateToken($username, $token, $adminRequired = false) {

        $user = User::getByName($username);

        if(self::isValidUser($user)) {

            if ($adminRequired and !$user->isAdmin()) {
                return null;
            }

            $passwordHash = $user->getPassword();
            $decrypted = self::tokenDecrypt($passwordHash, $token);

            $timestamp = $decrypted[0];
            $timeZone = date_default_timezone_get();
            date_default_timezone_set("UTC");

            if ($timestamp > time() or $timestamp < (time() - (60 * 30))) {
                return null;
            }
            date_default_timezone_set($timeZone);
            return $user;
        }

        return null;
    }

    /**
     * @param User $user
     * @param $password
     * @return bool
     */
    public static function verifyPassword($user, $password) {

        $password = self::preparePlainTextPassword($user->getName(), $password);

        if($user->getPassword()) { // do not allow logins for users without a password
            if(password_verify($password, $user->getPassword())) {
                if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT)) {
                    $user->setPassword(self::getPasswordHash($user->getName(), $password));
                    $user->save();
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    public static function isValidUser($user) {

        if($user instanceof User && $user->isActive() && $user->getId() && $user->getPassword()) {
            return true;
        }
        return false;
    }

    /**
     * @param $username
     * @param $plainTextPassword
     * @return bool|false|string
     * @throws \Exception
     */
    public static function getPasswordHash($username, $plainTextPassword) {
        $hash = password_hash(self::preparePlainTextPassword($username, $plainTextPassword), PASSWORD_DEFAULT);
        if(!$hash) {
            throw new \Exception("Unable to create password hash for user: " . $username);
        }
        return $hash;
    }

    /**
     * @param $username
     * @param $plainTextPassword
     * @return string
     */
    public static function preparePlainTextPassword($username, $plainTextPassword) {
        // plaintext password is prepared as digest A1 hash, this is to be backward compatible because this was
        // the former hashing algorithm in pimcore (< version 2.1.1)
        return md5($username . ":pimcore:" . $plainTextPassword);
    }

    /**
     * @param $username
     * @param $passwordHash
     * @return string
     */
    public static function generateToken($username, $passwordHash) {

        $algorithm = MCRYPT_TRIPLEDES;
        $mode = MCRYPT_MODE_ECB;

        $data = time() - 1 . '|' . $username;

        $key = $passwordHash;


        // append pkcs5 padding to the data
        $blocksize = mcrypt_get_block_size($algorithm, $mode);
        $pkcs = $blocksize - (strlen($data) % $blocksize);
        $data .= str_repeat(chr($pkcs), $pkcs);

        //encrypt
        $td = mcrypt_module_open($algorithm, '', $mode, '');

        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), null);
        $ks = mcrypt_enc_get_key_size($td);
        $key = substr($key, 0, $ks);
        mcrypt_generic_init($td, $key, $iv);
        $encrypted = mcrypt_generic($td, $data);
        $raw = base64_encode($encrypted);

        $token = "";
        for ($i = 0; $i < strlen($raw); $i++) {
            $token .= bin2hex($raw[$i]);
        }
        return $token;
    }

    /**
     * @static
     * @param  string $hex
     * @return  string
     */
    protected static function hex2str($hex) {
        $str = "";
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $str;
    }


    /**
     * @param $key
     * @param $token
     * @return array
     */
    public static function tokenDecrypt($key, $token) {

        $algorithm = MCRYPT_TRIPLEDES;
        $mode = MCRYPT_MODE_ECB;

        $encrypted = base64_decode(self::hex2str($token));


        $td = mcrypt_module_open($algorithm, '', $mode, '');

        //this takes up to 10 seconds ... WTF? Just use NULL ... ECB does not need an IV
        //$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), null);
        $iv = null;

        @mcrypt_generic_deinit($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $decrypted = str_replace(chr(8), "", $decrypted);
        return explode("|", $decrypted);
    }
}