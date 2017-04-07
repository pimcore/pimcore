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

namespace Pimcore\Security\Authentication;

use Pimcore\Model\Object\ClassDefinition\Data\Password;
use Pimcore\Model\Object\Concrete;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimpleFormAuthenticatorInterface;

/**
 * A user authenticator handling password fields on pimcore models. Can be used together with the `simple_form`
 * authentication provider.
 *
 * @see http://symfony.com/doc/current/security/custom_password_authenticator.html
 */
class PasswordFieldAuthenticator implements SimpleFormAuthenticatorInterface
{
    /**
     * @var string
     */
    protected $fieldName = 'password';

    /**
     * If true, the user password hash will be updated if necessary.
     *
     * @var bool
     */
    protected $updateHash = true;

    /**
     * Message used if credentials are invalid (user not found or password invalid)
     *
     * CAUTION: this message will be returned to the client
     *(so don't put any un-trusted messages / error strings here)
     *
     * @var string
     */
    protected $invalidCredentialsMessage = 'Invalid username or password';

    /**
     * @param string $fieldName
     */
    public function __construct($fieldName = 'password')
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @param bool $updateHash
     */
    public function setUpdateHash($updateHash)
    {
        $this->updateHash = (bool)$updateHash;
    }

    /**
     * @return string
     */
    public function getInvalidCredentialsMessage()
    {
        return $this->invalidCredentialsMessage;
    }

    /**
     * @param string $invalidCredentialsMessage
     */
    public function setInvalidCredentialsMessage($invalidCredentialsMessage)
    {
        $this->invalidCredentialsMessage = $invalidCredentialsMessage;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            // CAUTION: this message will be returned to the client
            // (so don't put any un-trusted messages / error strings here)
            throw new CustomUserMessageAuthenticationException($this->invalidCredentialsMessage);
        }

        if (!$user instanceof Concrete) {
            throw new RuntimeException('Returned user is no Pimcore model');
        }

        $passwordValid = $this
            ->getFieldDefinition($user)
            ->verifyPassword($token->getCredentials(), $user, $this->updateHash);

        if ($passwordValid) {
            return new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $providerKey,
                $user->getRoles()
            );
        }

        throw new CustomUserMessageAuthenticationException('Invalid username or password');
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }

    /**
     * @param Concrete $user
     *
     * @return Password
     */
    protected function getFieldDefinition(Concrete $user)
    {
        /* @var Password $passwordField */
        $field = $user->getClass()->getFieldDefinition($this->fieldName);

        if (!$field || !$field instanceof Password) {
            throw new RuntimeException(sprintf(
                'Field %s for user type %s is expected to be of type %s, %s given',
                $this->fieldName,
                get_class($user),
                Password::class,
                is_object($field) ? get_class($field) : gettype($field)
            ));
        }

        return $field;
    }
}
