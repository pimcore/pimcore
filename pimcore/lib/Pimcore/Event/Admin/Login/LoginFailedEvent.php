<?php

namespace Pimcore\Event\Admin\Login;

use Pimcore\Model\User;

class LoginFailedEvent
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
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function hasUser()
    {
        return null !== $this->user;
    }
}
