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
    protected $routes = [];

    /**
     * @var RequestMatcherInterface[]
     */
    protected $matchers;

    /**
     * @var RequestMatcherFactory
     */
    protected $requestMatcherFactory;

    /**
     * @param RequestMatcherFactory $factory
     */
    public function __construct(RequestMatcherFactory $factory)
    {
        $this->requestMatcherFactory = $factory;
    }

    /**
     * Add context specific routes
     *
     * @param string $context
     * @param array $routes
     */
    public function addContextRoutes($context, array $routes)
    {
        $this->routes[$context] = $routes;
    }

    /**
     * Guess the pimcore context
     *
     * @param Request $request
     * @return string
     */
    public function guess(Request $request)
    {
        /** @var RequestMatcherInterface[] $matchers */
        foreach ($this->getMatchers() as $context => $matchers) {
            foreach ($matchers as $matcher) {
                if ($matcher->matches($request)) {
                    return $context;
                }
            }
        }

        return PimcoreContextResolver::CONTEXT_DEFAULT;
    }

    /**
     * Get request matchers to query admin pimcore context from
     *
     * @return RequestMatcherInterface[]
     */
    protected function getMatchers()
    {
        if (null === $this->matchers) {
            foreach ($this->routes as $context => $routes) {
                $this->matchers[$context] = $this->requestMatcherFactory->buildRequestMatchers($routes);
            }
        }

        return $this->matchers;
    }
}
