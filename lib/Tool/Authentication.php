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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CryptoException;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class Authentication
{
    /**
     * @param Request|null $request
     *
     * @return User|null
     */
    public static function authenticateSession(Request $request = null): ?User
    {
        if (null === $request) {
            $request = \Pimcore::getContainer()->get('request_stack')->getCurrentRequest();

            if (null === $request) {
                return null;
            }
        }

        $session = $request->hasPreviousSession() ? $request->getSession() : null;

        if (null === $session) {
            return null;
        }

        $token = $session->get('_security_pimcore_admin');
        $token = $token ? static::safelyUnserialize($token) : null;

        if ($token instanceof TokenInterface) {
            $token = static::refreshUser($token, \Pimcore::getContainer()->get(UserProvider::class));
            $user = $token->getUser();

            if ($user instanceof \Pimcore\Security\User\User && self::isValidUser($user->getUser())) {
                return $user->getUser();
            }
        }

        return null;
    }

    protected static function safelyUnserialize(string $serializedToken): mixed
    {
        $token = null;
        $prevUnserializeHandler = ini_set('unserialize_callback_func', __CLASS__.'::handleUnserializeCallback');
        $prevErrorHandler = set_error_handler(static function (int $type, string $msg, string $file, int $line, array $context = []) use (&$prevErrorHandler) {
            if (__FILE__ === $file) {
                throw new \ErrorException($msg, 0x37313BC, $type, $file, $line);
            }

            return $prevErrorHandler ? $prevErrorHandler($type, $msg, $file, $line, $context) : false;
        });

        try {
            $token = unserialize($serializedToken);
        } catch (\ErrorException $e) {
            if (0x37313BC !== $e->getCode()) {
                throw $e;
            }
            Logger::warning('Failed to unserialize the security token from the session.', ['key' => 'pimcore_admin', 'received' => $serializedToken, 'exception' => $e]);
        } finally {
            restore_error_handler();
            ini_set('unserialize_callback_func', $prevUnserializeHandler);
        }

        return $token;
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback(string $class): never
    {
        throw new \ErrorException('Class not found: '.$class, 0x37313BC);
    }

    protected static function refreshUser(TokenInterface $token, UserProvider $provider): ?TokenInterface
    {
        $user = $token->getUser();

        if (!$provider->supportsClass($user::class)) {
            return null;
        }

        try {
            $token->setUser($provider->refreshUser($user));

            return $token;
        } catch (UserNotFoundException $e) {
            Logger::warning('Username could not be found in the selected user provider.', ['username' => $e->getUserIdentifier(), 'provider' => $provider::class]);

            return null;
        }
    }

    public static function authenticateToken(string $token, bool $adminRequired = false): ?User
    {
        $username = null;
        $timestamp = null;

        try {
            $decrypted = self::tokenDecrypt($token);
            list($timestamp, $username) = $decrypted;
        } catch (CryptoException $e) {
            return null;
        }

        $user = User::getByName($username);
        if (self::isValidUser($user)) {
            if ($adminRequired && !$user->isAdmin()) {
                return null;
            }

            try {
                $timeZone = date_default_timezone_get();
                date_default_timezone_set('UTC');

                if ($timestamp > time() || $timestamp < (time() - (60 * 60 * 24))) {
                    return null;
                }
            } finally {
                date_default_timezone_set($timeZone);
            }

            return $user;
        }

        return null;
    }

    public static function verifyPassword(User $user, string $password): bool
    {
        if (!$user->getPassword()) {
            // do not allow logins for users without a password
            return false;
        }

        $password = self::preparePlainTextPassword($user->getName(), $password);

        if (!password_verify($password, $user->getPassword())) {
            return false;
        }

        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['security']['password'];

        if (password_needs_rehash($user->getPassword(), $config['algorithm'], $config['options'])) {
            $user->setPassword(self::getPasswordHash($user->getName(), $password));
            $user->save();
        }

        return true;
    }

    public static function isValidUser(?User $user): bool
    {
        return $user instanceof User && $user->isActive() && $user->getId() && $user->getPassword();
    }

    /**
     * @param string $username
     * @param string $plainTextPassword
     *
     * @return string
     *
     * @throws \Exception
     *
     * @internal
     */
    public static function getPasswordHash(string $username, string $plainTextPassword): string
    {
        $password = self::preparePlainTextPassword($username, $plainTextPassword);
        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['security']['password'];

        if ($hash = password_hash($password, $config['algorithm'], $config['options'])) {
            return $hash;
        }

        throw new \Exception('Unable to create password hash for user: ' . $username);
    }

    private static function preparePlainTextPassword(string $username, string $plainTextPassword): string
    {
        // plaintext password is prepared as digest A1 hash, this is to be backward compatible because this was
        // the former hashing algorithm in pimcore (< version 2.1.1)
        return md5($username . ':pimcore:' . $plainTextPassword);
    }

    /**
     * @param string $username
     *
     * @return string
     *
     * @internal
     */
    public static function generateToken(string $username): string
    {
        $secret = \Pimcore::getContainer()->getParameter('secret');

        $data = time() - 1 . '|' . $username;
        $token = Crypto::encryptWithPassword($data, $secret);

        return $token;
    }

    private static function tokenDecrypt(string $token): array
    {
        $secret = \Pimcore::getContainer()->getParameter('secret');
        $decrypted = Crypto::decryptWithPassword($token, $secret);

        return explode('|', $decrypted);
    }
}
