<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\UserLoader;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EditmodeResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_EDITMODE = '_editmode';

    /**
     * @var UserLoader
     */
    protected $userLoader;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param RequestStack $requestStack
     * @param UserLoader $userLoader
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestStack $requestStack, UserLoader $userLoader, RequestHelper $requestHelper)
    {
        $this->userLoader    = $userLoader;
        $this->requestHelper = $requestHelper;

        parent::__construct($requestStack);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isEditmode(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        // editmode is only allowed for logged in users
        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return false;
        }

        $user = $this->userLoader->getUser();
        if (!$user) {
            return false;
        }

        // try to ready attribute from request - this allows sub-requests to define their
        // own editmode state
        if ($request->attributes->has(static::ATTRIBUTE_EDITMODE)) {
            return $request->attributes->get(static::ATTRIBUTE_EDITMODE);
        }

        // read editmode from request params
        if ($request->query->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }
}
