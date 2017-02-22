<?php

namespace Pimcore\Bundle\PimcoreBundle\Service;

use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class RequestMatcherFactory
{
    /**
     * Builds a set of request matchers from a config definition as configured in pimcore.admin.routes (see PimcoreBundle
     * configuration).
     *
     * @param array $entries
     * @return RequestMatcherInterface[]
     */
    public function buildRequestMatchers(array $entries)
    {
        $matchers = [];
        foreach ($entries as $entry) {
            $matchers[] = $this->buildRequestMatcher($entry);
        }

        return $matchers;
    }

    /**
     * @param array $entry
     * @return RequestMatcher
     */
    protected function buildRequestMatcher(array $entry)
    {
        // TODO add support for IPs, attributes and schemes if necessary
        $matcher = new RequestMatcher();

        if (isset($entry['path']) && $entry['path']) {
            $matcher->matchPath($entry['path']);
        }

        if (isset($entry['host']) && $entry['host']) {
            $matcher->matchHost($entry['host']);
        }

        if (isset($entry['methods']) && $entry['methods']) {
            $matcher->matchMethod($entry['methods']);
        }

        if (isset($entry['route']) && $entry['route']) {
            $matcher->matchAttribute('_route', $entry['route']);
        }

        return $matcher;
    }
}
