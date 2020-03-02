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

namespace Pimcore\Event\Admin\Login;

use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\Event;

class LoginFailedEvent extends Event
{
    /**
     * @var string
     */
    protected $credentials;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     *
     * @return mixed
     */
    public function getCredential($name, $default = null)
    {
        if (isset($this->credentials[$name])) {
            return $this->credentials[$name];
        }

        return $default;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasUser()
    {
        return null !== $this->user;
    }
}
