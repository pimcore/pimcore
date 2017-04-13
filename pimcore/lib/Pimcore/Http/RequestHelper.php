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
     *
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
            if ($request->query->get($key) || $request->request->get($key)) {
                return true;
            }
        }

        if (preg_match('@^/pimcore_document_tag_renderlet@', $request->getRequestUri())) {
            return true;
        }

        return false;
    }

    /**
     * Get an anonymized client IP from the request
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
    public function anonymizeIp($ip)
    {
        $aip = substr($ip, 0, strrpos($ip, '.') + 1);
        $aip .= '255';

        return $aip;
    }
}
