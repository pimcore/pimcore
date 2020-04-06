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

namespace Pimcore\Security\User;

use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider loading users from pimcore objects. To load users, the provider needs
 * to know which kind of users to load (className) and which field to query for the
 * username (usernameField).
 *
 * Example DI configuration loading from the AppBundle\Model\DataObject\User class and searching by username:
 *
 *      website_demo.security.user_provider:
 *          class: Pimcore\Security\User\ObjectUserProvider
 *          arguments: ['AppBundle\Model\DataObject\User', 'username']
 */
class ObjectUserProvider implements UserProviderInterface
{
    /**
     * The pimcore class name to be used. Needs to be a fully qualified class
     * name (e.g. Pimcore\Model\DataObject\User or your custom user class extending
     * the generated one.
     *
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $usernameField = 'username';

    /**
     * @param string $className
     * @param string $usernameField
     */
    public function __construct($className, $usernameField = 'username')
    {
        $this->setClassName($className);

        $this->usernameField = $usernameField;
    }

    /**
     * @param string $className
     */
    protected function setClassName($className)
    {
        if (empty($className)) {
            throw new InvalidArgumentException('Object class name is empty');
        }

        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('User class %s does not exist', $className));
        }

        $reflector = new \ReflectionClass($className);
        if (!$reflector->isSubclassOf(AbstractObject::class)) {
            throw new InvalidArgumentException(sprintf('User class %s must be a subclass of %s', $className, AbstractObject::class));
        }

        $this->className = $className;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        $getter = sprintf('getBy%s', ucfirst($this->usernameField));

        // User::getByUsername($username, 1);
        $user = call_user_func_array([$this->className, $getter], [$username, 1]);
        if ($user && $user instanceof $this->className) {
            return $user;
        }

        throw new UsernameNotFoundException(sprintf('User %s was not found', $username));
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof $this->className || !$user instanceof AbstractObject) {
            throw new UnsupportedUserException();
        }

        $refreshedUser = call_user_func_array([$this->className, 'getById'], [$user->getId()]);

        return $refreshedUser;
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === $this->className;
    }
}
