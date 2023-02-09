<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Http;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RequestContext;

class RequestHelper
{
    const ATTRIBUTE_FRONTEND_REQUEST = '_pimcore_frontend_request';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var requestContext
     */
    protected $requestContext;

    /**
     * @param RequestStack $requestStack
     * @param RequestContext $requestContext
     */
    public function __construct(RequestStack $requestStack, RequestContext $requestContext)
    {
        $this->requestStack = $requestStack;
        $this->requestContext = $requestContext;
    }

    /**
     * @return bool
     */
    public function hasCurrentRequest(): bool
    {
        return null !== $this->requestStack->getCurrentRequest();
    }

    /**
     * @return Request
     */
    public function getCurrentRequest(): Request
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new \LogicException('A Request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param Request|null $request
     *
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
     * @deprecated will be removed in Pimcore 11, use getMainRequest() instead
     *
     * @return bool
     */
    public function hasMasterRequest(): bool
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.2',
            sprintf('%s is deprecated, please use RequestHelper::hasMainRequest() instead.', __METHOD__)
        );

        return $this->hasMainRequest();
    }

    /**
     * @return bool
     */
    public function hasMainRequest(): bool
    {
        return null !== $this->requestStack->getMainRequest();
    }

    /**
     * @deprecated will be removed in Pimcore 11 - use getMainRequest() instead
     *
     * @return Request
     */
    public function getMasterRequest(): Request
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.2',
            sprintf('%s is deprecated, please use RequestHelper::getMainRequest() instead.', __METHOD__)
        );

        return $this->getMainRequest();
    }

    /**
     * @return Request
     */
    public function getMainRequest(): Request
    {
        $masterRequest = $this->requestStack->getMainRequest();
        if (null === $masterRequest) {
            throw new \LogicException('There is no main request available.');
        }

        return $masterRequest;
    }

    /**
     * @param Request|null $request
     *
     * @return bool
     */
    public function isFrontendRequest(Request $request = null): bool
    {
        $request = $this->getRequest($request);
        $attribute = self::ATTRIBUTE_FRONTEND_REQUEST;

        if ($request->attributes->has($attribute)) {
            return (bool)$request->attributes->get($attribute);
        }

        $frontendRequest = $this->detectFrontendRequest($request);

        $request->attributes->set($attribute, $frontendRequest);

        return $frontendRequest;
    }

    /**
     * TODO use pimcore context here?
     *
     * @param Request $request
     *
     * @return bool
     */
    private function detectFrontendRequest(Request $request): bool
    {
        if (\Pimcore::inAdmin()) {
            return false;
        }

        if (preg_match('@^/admin.*@', $request->getRequestUri())) {
            return false;
        }

        return true;
    }

    /**
     * E.g. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     *
     * @param Request|null $request
     *
     * @return bool
     */
    public function isFrontendRequestByAdmin(Request $request = null): bool
    {
        $request = $this->getRequest($request);

        $keys = [
            'pimcore_editmode',
            'pimcore_preview',
            'pimcore_admin',
            'pimcore_object_preview',
            'pimcore_version',
        ];

        foreach ($keys as $key) {
            if ($request->query->get($key) || $request->request->get($key)) {
                return true;
            }
        }

        if (preg_match('@^/admin/document_tag/renderlet@', $request->getRequestUri())) {
            return true;
        }

        return false;
    }

    /**
     * Get an anonymized client IP from the request
     *
     * @internal
     *
     * @param Request|null $request
     *
     * @return string
     */
    public function getAnonymizedClientIp(Request $request = null)
    {
        $request = $this->getRequest($request);

        return $this->anonymizeIp($request->getClientIp());
    }

    /**
     * Anonymize IP: replace the last octet with 255
     *
     * @param string $ip
     *
     * @return string
     */
    private function anonymizeIp(string $ip)
    {
        $aip = substr($ip, 0, strrpos($ip, '.') + 1);
        $aip .= '255';

        return $aip;
    }

    /**
     * @internal
     *
     * @param string $uri
     *
     * @return Request
     */
    public function createRequestWithContext($uri = '/')
    {
        $port = '';
        $scheme = $this->requestContext->getScheme();

        if ('http' === $scheme && 80 !== $this->requestContext->getHttpPort()) {
            $port = ':'.$this->requestContext->getHttpPort();
        } elseif ('https' === $scheme && 443 !== $this->requestContext->getHttpsPort()) {
            $port = ':'.$this->requestContext->getHttpsPort();
        }

        $request = Request::create(
            $scheme .'://'. $this->requestContext->getHost().$port.$this->requestContext->getBaseUrl().$uri,
            $this->requestContext->getMethod(),
            $this->requestContext->getParameters()
        );

        return $request;
    }

    /**
     * Gets the current session from RequestStack
     *
     * @throws SessionNotFoundException
     */
    public function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
