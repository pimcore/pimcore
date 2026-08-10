<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tool;

use __PHP_Incomplete_Class;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CryptoException;
use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\User;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class Authentication
{
    public static function authenticateSession(?Request $request = null): ?User
    {
        if (null === $request) {
            $request = Pimcore::getContainer()->get('request_stack')->getCurrentRequest();

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
            $token = static::refreshUser($token, Pimcore::getContainer()->get(UserProvider::class));
            $user = $token->getUser();

            if ($user instanceof \Pimcore\Security\User\User && self::isValidUser($user->getUser())) {
                $pimcoreUser = $user->getUser();
                // update last login date if last login timestamp was more than few seconds ago
                // this is to reduce potential many last login update queries within a small time frame
                if ($pimcoreUser->getLastLogin() <= time() - 15) {
                    $pimcoreUser->setLastLogin(time());
                    $pimcoreUser->setLastLoginDate(); //set user current login date
                }

                return $pimcoreUser;
            }
        }

        return null;
    }

    protected static function safelyUnserialize(string $serializedToken): mixed
    {
        // Restrict deserialization to the known session-token object graph. Any other class
        // (e.g. a PHP object-injection gadget planted in the session store) is reduced to
        // __PHP_Incomplete_Class, so none of its magic methods (__wakeup/__destruct/...) run.
        $token = @unserialize($serializedToken, ['allowed_classes' => self::getSessionTokenAllowedClasses()]);

        // A tampered or foreign payload deserializes to false or an incomplete class; the
        // caller additionally requires an instanceof TokenInterface, so we just drop it here.
        if ($token === false || $token instanceof __PHP_Incomplete_Class) {
            Logger::warning('Failed to safely unserialize the security token from the session.', ['key' => 'pimcore_admin']);

            return null;
        }

        return $token;
    }

    /**
     * Classes that legitimately occur in a serialized "pimcore_admin" session security token.
     * Custom authenticators can extend this list via the
     * `pimcore.security.session_token_allowed_classes` container parameter.
     *
     * @return array<int, class-string>
     *
     * @internal
     */
    public static function getSessionTokenAllowedClasses(): array
    {
        $allowedClasses = [
            \Pimcore\Security\User\User::class,
            User::class,
            PostAuthenticationToken::class,
            UsernamePasswordToken::class,
            // The classic admin-ui-classic-bundle "/admin/login" firewall (Pimcore < 2026.1 and the
            // LTS line) stores a PostAuthenticationToken normally, or this subclass while 2FA is
            // pending. Referenced by name because admin-ui-classic-bundle is not a core dependency.
            'Pimcore\\Bundle\\AdminBundle\\Security\\Authentication\\Token\\TwoFactorRequiredToken',
        ];

        // scheb/2fa stores a TwoFactorToken (wrapping the authenticated token) during the 2FA step.
        if (class_exists(\Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken::class)) {
            $allowedClasses[] = \Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken::class;
        }

        if (Pimcore::hasContainer()) {
            try {
                $extraAllowedClasses = (array) Pimcore::getContainer()->getParameter('pimcore.security.session_token_allowed_classes');
                $allowedClasses = array_merge(
                    $allowedClasses,
                    array_values(array_filter($extraAllowedClasses, 'is_string'))
                );
            } catch (InvalidArgumentException) {
                // parameter not configured – use defaults
            }
        }

        return $allowedClasses;
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
        try {
            [$timestamp, $user] = self::tokenDecrypt($token);
        } catch (CryptoException|NotFoundException) {
            return null;
        }

        if (self::isValidUser($user)) {
            $user = User::getById($user->getId());

            // expiring the token
            $user->setPasswordRecoveryToken(null);
            $user->save();

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

        if (!password_verify(self::preparePlainTextPassword($user->getName(), $password), $user->getPassword())) {
            return false;
        }

        $config = Config::getSystemConfiguration()['security']['password'];

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
     *
     *
     * @throws Exception
     *
     * @internal
     */
    public static function getPasswordHash(string $username, string $plainTextPassword): string
    {
        $password = self::preparePlainTextPassword($username, $plainTextPassword);

        try {
            $config = Config::getSystemConfiguration()['security']['password'];
        } catch (Exception $e) {
            // default config in case kernel is not booted yet (e.g. in installer)
            $config = [
                'algorithm' => PASSWORD_DEFAULT,
                'options' => [],
            ];
        }

        if ($hash = password_hash($password, $config['algorithm'], $config['options'])) {
            return $hash;
        }

        throw new Exception('Unable to create password hash for user: ' . $username);
    }

    private static function preparePlainTextPassword(string $username, string $plainTextPassword): string
    {
        // plaintext password is prepared as digest A1 hash, this is to be backward compatible because this was
        // the former hashing algorithm in pimcore (< version 2.1.1)
        return md5($username . ':pimcore:' . $plainTextPassword);
    }

    /**
     * @internal
     */
    public static function generateToken(string $username): string
    {
        $user = User::getByName($username);

        return self::generateTokenByUser($user);
    }

    /**
     * @internal
     */
    public static function generateTokenByUser(User $user): string
    {
        $secret = Pimcore::getContainer()->getParameter('secret');

        $data = time() - 1 . '|' . $user->getName();
        $token = Crypto::encryptWithPassword($data, $secret);

        $user->setPasswordRecoveryToken($token);
        $user->save();

        return $token;
    }

    /**
     * @throws NotFoundException if token does not belong to any user
     * @throws CryptoException
     */
    private static function tokenDecrypt(string $token): array
    {
        $user = new User();
        $user->getDao()->getByPasswordRecoveryToken($token);

        $secret = Pimcore::getContainer()->getParameter('secret');
        $decrypted = Crypto::decryptWithPassword($token, $secret);

        $explode = explode('|', $decrypted);

        return [$explode[0], $user];
    }
}
