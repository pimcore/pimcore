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

namespace Pimcore\Http;

use DateTime;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
class ResponseHelper
{
    /**
     * Disable cache
     *
     */
    public function disableCache(Response $response, bool $force = false): void
    {
        if (!$response->headers->has('Cache-Control') || $force) {
            // set this headers to avoid problems with proxies, ...
            foreach (['no-cache', 'private', 'no-store', 'must-revalidate', 'no-transform'] as $directive) {
                $response->headers->addCacheControlDirective($directive, true);
            }

            foreach (['max-stale', 'post-check', 'pre-check', 'max-age'] as $directive) {
                $response->headers->addCacheControlDirective($directive, '0');
            }

            // this is for mod_pagespeed
            $response->headers->addCacheControlDirective('no-transform', true);
        }

        if (!$response->headers->has('Pragma') || $force) {
            $response->headers->set('Pragma', 'no-cache', true);
        }

        if (!$response->headers->has('Expires') || $force) {
            $response->setExpires(new DateTime('Tue, 01 Jan 1980 00:00:00 GMT'));
        }
    }

    public function isHtmlResponse(Response $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if (str_contains((string)$response->getContent(), '<html')) {
            return true;
        }

        return false;
    }
}
