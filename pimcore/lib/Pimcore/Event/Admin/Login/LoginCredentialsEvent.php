<?php

namespace Pimcore\Event\Admin\Login;

use Pimcore\Event\Traits\RequestAwareTrait;
use Symfony\Component\HttpFoundation\Request;

class LoginCredentialsEvent
{
    use RequestAwareTrait;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @param Request $request
     * @param array $credentials
     */
    public function __construct(Request $request, array $credentials)
    {
        $this->request     = $request;
        $this->credentials = $credentials;
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param array $credentials
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
    }
}
