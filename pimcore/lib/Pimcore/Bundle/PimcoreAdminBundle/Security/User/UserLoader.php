<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\User;

use Pimcore\Http\RequestHelper;
use Pimcore\Model\User as UserModel;
use Pimcore\Tool\Authentication;

/**
 * Loads user either from token storage (when inside admin firewall) or directly from session and keeps it in cache. This
 * is mainly needed from event listeners outside the admin firewall to access the user object without needing to open the
 * session multiple times.
 */
class UserLoader
{
    /**
     * @var UserModel
     */
    protected $user;

    /**
     * @var TokenStorageUserResolver
     */
    protected $userResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param TokenStorageUserResolver $userResolver
     * @param RequestHelper $requestHelper
     */
    public function __construct(TokenStorageUserResolver $userResolver, RequestHelper $requestHelper)
    {
        $this->userResolver  = $userResolver;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @return UserModel
     */
    public function getUser()
    {
        if (null === $this->user) {
            $user = $this->loadUser();

            if ($user) {
                $this->user = $user;
            }
        }

        return $this->user;
    }

    /**
     * @return UserModel|null
     */
    protected function loadUser()
    {
        // authenticated admin user inside admin firewall and set on token storage
        if ($user = $this->userResolver->getUser()) {
            return $user;
        }

        // try to directly authenticate
        if ($this->requestHelper->isFrontendRequestByAdmin()) {
            return Authentication::authenticateSession();
        }
    }
}
