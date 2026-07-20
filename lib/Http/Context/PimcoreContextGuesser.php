<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Http\Context;

use Pimcore\Http\RequestMatcherFactory;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class PimcoreContextGuesser
{
    private array $routes = [];

    private ?array $matchers = null;

    private RequestMatcherFactory $requestMatcherFactory;

    public function __construct(RequestMatcherFactory $factory)
    {
        $this->requestMatcherFactory = $factory;
    }

    /**
     * Add context specific routes
     *
     */
    public function addContextRoutes(string $context, array $routes): void
    {
        $this->routes[$context] = $routes;
    }

    /**
     * Guess the pimcore context
     *
     */
    public function guess(Request $request, string $default): string
    {
        foreach ($this->getMatchers() as $context => $matchers) {
            /** @var array $matcher */
            foreach ($matchers as $matcher) {
                $chainRequestMatcher = new ChainRequestMatcher($matcher);
                if ($chainRequestMatcher->matches($request)) {
                    return $context;
                }
            }
        }

        return $default;
    }

    /**
     * Get request matchers to query admin pimcore context from
     *
     */
    private function getMatchers(): array
    {
        if (null === $this->matchers) {
            $this->matchers = [];

            foreach ($this->routes as $context => $routes) {
                $this->matchers[$context] = $this->requestMatcherFactory->buildRequestMatchers($routes);
            }
        }

        return $this->matchers;
    }
}
