<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class RequestTypeGuesser
{
    /**
     * @var array
     */
    protected $adminRoutes = [];

    /**
     * @var RequestMatcherInterface
     */
    protected $adminMatchers;

    /**
     * @param array $adminRoutes
     */
    public function __construct(array $adminRoutes)
    {
        $this->adminRoutes = $adminRoutes;
    }

    /**
     * Guess the request type
     *
     * @param Request $request
     * @return string
     */
    public function guess(Request $request)
    {
        if ($this->isAdminRequest($request)) {
            return RequestTypeResolver::REQUEST_TYPE_ADMIN;
        }

        return RequestTypeResolver::REQUEST_TYPE_DEFAULT;
    }

    /**
     * Get request matchers to query admin request type from
     *
     * @return RequestMatcherInterface[]
     */
    protected function getAdminMatchers()
    {
        // TODO use generators to save memory?
        if (null === $this->adminMatchers) {
            $this->adminMatchers = [];
            foreach ($this->adminRoutes as $route) {
                $this->adminMatchers[] = $this->buildAdminMatcher($route);
            }
        }

        return $this->adminMatchers;
    }

    /**
     * Build a request matcher for a config route
     *
     * @param array $route
     * @return RequestMatcherInterface
     */
    protected function buildAdminMatcher(array $route)
    {
        $matcher = new RequestMatcher();

        if ($route['path']) {
            $matcher->matchPath($route['path']);
        }

        if ($route['host']) {
            $matcher->matchHost($route['host']);
        }

        if ($route['methods']) {
            $matcher->matchMethod($route['methods']);
        }

        if ($route['route']) {
            $matcher->matchAttribute('_route', $route['route']);
        }

        return $matcher;
    }

    /**
     * Match request against admin patterns
     *
     * @param Request $request
     * @return bool
     */
    protected function isAdminRequest(Request $request)
    {
        foreach ($this->getAdminMatchers() as $adminMatcher) {
            if ($adminMatcher->matches($request)) {
                return true;
            }
        }

        return false;
    }
}
