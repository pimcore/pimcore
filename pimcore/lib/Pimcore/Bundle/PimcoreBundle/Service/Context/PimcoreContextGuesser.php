<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Context;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Bundle\PimcoreBundle\Service\RequestMatcherFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class PimcoreContextGuesser
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
     * @var RequestMatcherFactory
     */
    protected $requestMatcherFactory;

    /**
     * @param RequestMatcherFactory $factory
     * @param array $adminRoutes
     */
    public function __construct(RequestMatcherFactory $factory, array $adminRoutes)
    {
        $this->requestMatcherFactory = $factory;
        $this->adminRoutes           = $adminRoutes;
    }

    /**
     * Guess the pimcore context
     *
     * @param Request $request
     * @return string
     */
    public function guess(Request $request)
    {
        if ($this->isAdminRequest($request)) {
            return PimcoreContextResolver::CONTEXT_ADMIN;
        }

        return PimcoreContextResolver::CONTEXT_DEFAULT;
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

    /**
     * Get request matchers to query admin pimcore context from
     *
     * @return RequestMatcherInterface[]
     */
    protected function getAdminMatchers()
    {
        if (null === $this->adminMatchers) {
            $this->adminMatchers = $this->requestMatcherFactory->buildRequestMatchers($this->adminRoutes);
        }

        return $this->adminMatchers;
    }
}
