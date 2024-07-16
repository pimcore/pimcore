<?php

declare(strict_types=1);

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

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Controller\Attribute\ResponseHeader;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ResponseHeaderResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_RESPONSE_HEADER = '_response_header';

    /**
     * Get response headers which were added to the request either by annotation
     * or manually.
     *
     *
     * @return ResponseHeader[]
     */
    public function getResponseHeaders(Request $request = null): array
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_RESPONSE_HEADER, []);
    }

    /**
     * We don't have a response object at this point, but we can add headers here which will be
     * set by the ResponseHeaderListener which reads and adds this headers in the kernel.response event.
     *
     */
    public function addResponseHeader(Request $request, string $key, array|string $values, bool $replace = false): void
    {
        // the array of headers set by the ResponseHeader attribute
        $responseHeaders = $this->getResponseHeaders($request);

        // manually add a #[ResponseHeader] attribute to the list of headers
        $responseHeaders[] = new ResponseHeader($key, $values, $replace);

        $request->attributes->set(static::ATTRIBUTE_RESPONSE_HEADER, $responseHeaders);
    }
}
