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

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\AttributesRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * @internal
 */
class RequestMatcherFactory
{
    /**
     * Builds a set of request matchers from a config definition as configured in pimcore.admin.routes (see PimcoreCoreBundle
     * configuration).
     *
     * @param array $entries
     *
     * @return RequestMatcherInterface[]
     */
    public function buildRequestMatchers(array $entries): array
    {
        $matchers = [];
        foreach ($entries as $entry) {
            $matchers[] = $this->buildRequestMatcher($entry);
        }

        return $matchers;
    }

    /**
     * Builds a request matcher from a route configuration
     *
     * @param array $entry
     *
     * @return array
     */
    public function buildRequestMatcher(array $entry): array
    {
        $matchers = [];

        if (isset($entry['path']) && $entry['path']) {
            $matchers[] = new PathRequestMatcher($entry['path']);
        }

        if (isset($entry['host']) && $entry['host']) {
            $matchers[] = new HostRequestMatcher($entry['host']);
        }

        if (isset($entry['methods']) && $entry['methods']) {
            $matchers[] = new MethodRequestMatcher($entry['methods']);
        }

        if (isset($entry['route']) && $entry['route']) {
            $matchers[] = new AttributesRequestMatcher(['_route' => $entry['route']]);
        }

        return $matchers;
    }
}
