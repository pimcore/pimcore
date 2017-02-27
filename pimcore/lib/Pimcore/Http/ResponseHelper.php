<?php

namespace Pimcore\Http;

use Symfony\Component\HttpFoundation\Response;

class ResponseHelper
{
    /**
     * Disable cache
     *
     * @param Response $response
     * @param bool $force
     */
    public function disableCache(Response $response, $force = false)
    {
        if (!$response->headers->has('Cache-Control') || $force) {
            // set this headers to avoid problems with proxies, ...
            foreach (['no-cache', 'private', 'no-store', 'must-revalidate', 'no-transform'] as $directive) {
                $response->headers->addCacheControlDirective($directive, true);
            }

            foreach (['max-stale', 'post-check', 'pre-check', 'max-age'] as $directive) {
                $response->headers->addCacheControlDirective($directive, 0);
            }

            // this is for mod_pagespeed
            $response->headers->addCacheControlDirective('no-transform', true);
        }

        if (!$response->headers->has('Pragma') || $force) {
            $response->headers->set('Pragma', 'no-cache', true);
        }

        if (!$response->headers->has('Expires') || $force) {
            $response->setExpires(new \DateTime('Tue, 01 Jan 1980 00:00:00 GMT'));
        }
    }
}
