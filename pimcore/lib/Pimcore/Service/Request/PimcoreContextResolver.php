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

namespace Pimcore\Service\Request;

use Pimcore\Service\Context\PimcoreContextGuesser;
use Pimcore\Service\RequestMatcherFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets/sets and guesses pimcore context (admin, default) from request. The guessing is implemented in PimcoreContextGuesser
 * and matches the request against a list of paths and routes which are exposed via config.
 */
class PimcoreContextResolver extends AbstractRequestResolver
{
    const CONTEXT_ADMIN = 'admin';
    const CONTEXT_WEBSERVICE = 'webservice';
    const CONTEXT_DEFAULT = 'default';

    /**
     * @var string
     */
    protected $pimcoreContext;

    /**
     * @var PimcoreContextGuesser
     */
    protected $guesser;

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
     * @inheritDoc
     */
    public function __construct(RequestStack $requestStack, RequestMatcherFactory $requestMatcherFactory)
    {
        $this->requestMatcherFactory = $requestMatcherFactory;

        parent::__construct($requestStack);
    }

    /**
     * Get pimcore context from request
     *
     * @param Request|null $request
     *
     * @return string|null
     */
    public function getPimcoreContext(Request $request = null)
    {
        if (!$this->pimcoreContext) {
            if (null === $request) {
                // per default the pimcore context always depends on the master request
                $request = $this->getMasterRequest();
            }

            $context = $this->guess($request);
            $this->pimcoreContext = $context;
        }

        return $this->pimcoreContext;
    }

    /**
     * @param string $context
     */
    public function setPimcoreContext($context)
    {
        $this->pimcoreContext = $context;
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
     *
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

        return self::CONTEXT_DEFAULT;
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
