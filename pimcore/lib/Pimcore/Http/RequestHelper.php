<?php

namespace Pimcore\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return Request
     */
    public function getCurrentRequest()
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new \LogicException('A Request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param Request|null $request
     * @return Request
     */
    public function getRequest(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request;
    }

    /**
     * @return Request
     */
    public function getMasterRequest()
    {
        $masterRequest = $this->requestStack->getMasterRequest();
        if (null === $masterRequest) {
            throw new \LogicException('There is no master request available.');
        }

        return $masterRequest;
    }

    /**
     * E.g. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     *
     * @param Request|null $request
     * @return bool
     */
    public function isFrontendRequestByAdmin(Request $request = null)
    {
        $request = $this->getRequest($request);

        $keys = [
            'pimcore_editmode',
            'pimcore_preview',
            'pimcore_admin',
            'pimcore_object_preview',
            'pimcore_version'
        ];

        foreach ($keys as $key) {
            if ($request->request->has($key)) {
                return true;
            }
        }

        if (preg_match('@^/pimcore_document_tag_renderlet@', $request->getRequestUri())) {
            return true;
        }

        return false;
    }
}
