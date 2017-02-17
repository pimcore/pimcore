<?php

namespace Pimcore\Bundle\PimcoreBundle\Security\Firewall;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This does exactly the same as the Firewall\ContextListener does to populate the token storage from the session and
 * allows us to access tokens from another firewall outside the firewall context. Might be a little hacky to reproduce
 * the logic here but is the only way to access a user from another firewall (e.g. access admin token from editmode).
 *
 * Keep an eye on the ContextListener and reproduce those changes here!
 */
class TokenLoader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var UserProviderInterface[]
     */
    protected $userProviders;

    /**
     * @param Session $session
     * @param RequestStack $requestStack
     * @param UserProviderInterface[] $userProviders
     */
    public function __construct(Session $session, RequestStack $requestStack, array $userProviders = [])
    {
        $this->session       = $session;
        $this->requestStack  = $requestStack;
        $this->userProviders = $userProviders;
    }

    /**
     * Get a security token from a defined firewall
     *
     * @param string $firewall
     * @param Request|null $request
     * @param bool $refresh
     *
     * @return null|TokenInterface
     */
    public function getToken($firewall, Request $request = null, $refresh = true)
    {
        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (null ===  $request) {
            throw new \RuntimeException('Request is not available');
        }

        $key     = '_security_' . $firewall;
        $session = $request->hasPreviousSession() ? $request->getSession() : null;

        if (null === $session || null === $token = $session->get($key)) {
            return;
        }

        $token = unserialize($token);

        if (null !== $this->logger) {
            $this->logger->debug('Read existing security token from the session.', array('key' => $key));
        }

        if ($token instanceof TokenInterface) {
            if ($refresh) {
                $token = $this->refreshUser($token);
            }
        } elseif (null !== $token) {
            if (null !== $this->logger) {
                $this->logger->warning('Expected a security token from the session, got something else.', array('key' => $key, 'received' => $token));
            }

            $token = null;
        }

        return $token;
    }

    /**
     * Refreshes the user by reloading it from the user provider.
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface|null
     *
     * @throws \RuntimeException
     */
    protected function refreshUser(TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return $token;
        }

        foreach ($this->userProviders as $provider) {
            try {
                $refreshedUser = $provider->refreshUser($user);
                $token->setUser($refreshedUser);

                if (null !== $this->logger) {
                    $this->logger->debug('User was reloaded from a user provider.', array('username' => $refreshedUser->getUsername(), 'provider' => get_class($provider)));
                }

                return $token;
            } catch (UnsupportedUserException $e) {
                // let's try the next user provider
            } catch (UsernameNotFoundException $e) {
                if (null !== $this->logger) {
                    $this->logger->warning('Username could not be found in the selected user provider.', array('username' => $e->getUsername(), 'provider' => get_class($provider)));
                }

                return null;
            }
        }

        throw new \RuntimeException(sprintf('There is no user provider for user "%s".', get_class($user)));
    }
}
