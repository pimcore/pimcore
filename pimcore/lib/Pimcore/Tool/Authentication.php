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

use Defuse\Crypto\Crypto;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Request;

class Authentication
{

    /**
     * @param $username
     * @param $password
     * @return null|User
     */
    public static function authenticatePlaintext($username, $password)
    {
        /** @var User $user */
        $user = User::getByName($username);

        // user needs to be active, needs a password and an ID (do not allow system user to login, ...)
        if (self::isValidUser($user)) {
            if (self::verifyPassword($user, $password)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @static
     *
     * @param Request $request
     *
     * @return User
     */
    public static function authenticateSession(Request $request = null)
    {
        if (null === $request) {
            $request = \Pimcore::getContainer()->get('pimcore.http.request_helper')->getCurrentRequest();
        }

        if (!Session::requestHasSessionId($request, true)) {
            // if no session cookie / ID no authentication possible, we don't need to start a session
            return null;
        }

        $session = Session::getReadOnly();
        $user = $session->get('user');

        if ($user instanceof User) {
            // renew user
            $user = User::getById($user->getId());

            if (self::isValidUser($user)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @static
     * @throws \Exception
     * @return User
     */
    public static function authenticateHttpBasic()
    {

        // we're using Sabre\HTTP for basic auth
        $request = \Sabre\HTTP\Sapi::getRequest();
        $response = new \Sabre\HTTP\Response();
        $auth = new \Sabre\HTTP\Auth\Basic(Tool::getHostname(), $request, $response);
        $result = $auth->getCredentials();

        if (is_array($result)) {
            list($username, $password) = $result;
            $user = self::authenticatePlaintext($username, $password);
            if ($user) {
                return $user;
            }
        }

        $auth->requireLogin();
        $response->setBody("Authentication required");
        Logger::error("Authentication Basic (WebDAV) required");
        \Sabre\HTTP\Sapi::sendResponse($response);
        die();
    }

    /**
     * @param $username
     * @param $token
     * @param bool $adminRequired
     * @return null|User
     */
    public static function authenticateToken($username, $token, $adminRequired = false)
    {
        $user = User::getByName($username);

        if (self::isValidUser($user)) {
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
    public static function verifyPassword($user, $password)
    {
        $password = self::preparePlainTextPassword($user->getName(), $password);

        if ($user->getPassword()) { // do not allow logins for users without a password
            if (password_verify($password, $user->getPassword())) {
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
    public static function isValidUser($user)
    {
        if ($user instanceof User && $user->isActive() && $user->getId() && $user->getPassword()) {
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
    public static function getPasswordHash($username, $plainTextPassword)
    {
        $hash = password_hash(self::preparePlainTextPassword($username, $plainTextPassword), PASSWORD_DEFAULT);
        if (!$hash) {
            throw new \Exception("Unable to create password hash for user: " . $username);
        }

        return $hash;
    }

    /**
     * @param $username
     * @param $plainTextPassword
     * @return string
     */
    public static function preparePlainTextPassword($username, $plainTextPassword)
    {
        // plaintext password is prepared as digest A1 hash, this is to be backward compatible because this was
        // the former hashing algorithm in pimcore (< version 2.1.1)
        return md5($username . ":pimcore:" . $plainTextPassword);
    }

    /**
     * @param $username
     * @param $passwordHash
     * @return string
     */
    public static function generateToken($username, $passwordHash)
    {
        $data = time() - 1 . '|' . $username;
        $token = Crypto::encryptWithPassword($data, $passwordHash);

        return $token;
    }

    /**
     * @param $passwordHash
     * @param $token
     * @return array
     */
    public static function tokenDecrypt($passwordHash, $token)
    {
        $decrypted = Crypto::decryptWithPassword($token, $passwordHash);

        return explode("|", $decrypted);
    }
}
