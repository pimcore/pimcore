<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class FormAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->router->generate('admin_login');

        return new RedirectResponse($url);
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/admin/login' || !$request->isMethod('POST')) {
            return null;
        }

        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password')
        ];

        return $credentials;
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$userProvider instanceof InMemoryUserProvider) {
            return null;
        }

        try {
            return $userProvider->loadUserByUsername($credentials['username']);
        } catch (UsernameNotFoundException $e) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials');
        }
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($user->getPassword() === $credentials['password']) {
            return true;
        }

        throw new CustomUserMessageAuthenticationException('Invalid credentials');
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        $url = $this->router->generate('admin_login');

        return new RedirectResponse($url);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $url = $this->router->generate('admin_index');

        return new RedirectResponse($url);
    }

    /**
     * @inheritDoc
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
