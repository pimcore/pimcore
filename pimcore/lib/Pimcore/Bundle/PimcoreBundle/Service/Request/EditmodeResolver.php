<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\UserLoader;
use Pimcore\Http\RequestHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EditmodeResolver extends AbstractRequestResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

        // try to ready attribute from request - this allows sub-requests to define their
        // own editmode state
        if ($request->attributes->has(static::ATTRIBUTE_EDITMODE)) {
            return $request->attributes->get(static::ATTRIBUTE_EDITMODE);
        }

        $logData = [
            'param'        => false,
            'adminRequest' => false,
            'user'         => false
        ];

        // read editmode from request params
        $result = false;
        if ($request->query->get('pimcore_editmode')) {
            $logData['param'] = true;
            $result = true;
        }

        if ($result) {
            // editmode is only allowed for logged in users
            if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
                $result = false;
            } else {
                $logData['adminRequest'] = true;
            }

            $user = $this->userLoader->getUser();
            if (!$user) {
                $result = false;
            } else {
                $logData['user'] = true;
            }
        }

        $this->logger->debug('Resolved editmode to {editmode}', [
            'editmode' => $result ? 'true' : 'false',
            'params'   => $logData
        ]);

        $request->attributes->set(static::ATTRIBUTE_EDITMODE, $result);

        // TODO this can be removed later
        \Pimcore\Cache\Runtime::set('pimcore_editmode', $result);

        return $result;
    }
}
