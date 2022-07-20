<?php

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

namespace Pimcore\Routing\Element;

use Pimcore\Http\RequestHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * A custom router implementation handling pimcore elements.
 */
class Router implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface
{
    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param RequestContext $context
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestContext $context, RequestHelper $requestHelper)
    {
        $this->context = $context;
        $this->requestHelper = $requestHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @return RequestContext
     */
    public function getContext()// : RequestContext
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function supports($name)// : bool
    {
        return $name === 'pimcore_element';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRouteDebugMessage($name, array $parameters = [])// : string
    {
        $element = $parameters['element'] ?? null;
        if ($element instanceof ElementInterface) {
            return sprintf('Element (Type: %s, ID: %d)', $element->getType(), $element->getId());
        }

        return 'No element';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH)// : string
    {
        $element = $parameters['element'] ?? null;
        unset($parameters['element']);
        if ($element instanceof Document || $element instanceof Asset) {
            $schemeAuthority = '';
            $host = $this->context->getHost();
            $scheme = $this->context->getScheme();
            $path = $element->getFullPath();
            $needsHostname = self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType;

            if (strpos($path, '://') !== false) {
                $host = parse_url($path, PHP_URL_HOST);
                $scheme = parse_url($path, PHP_URL_SCHEME);
                $path = parse_url($path, PHP_URL_PATH);
                $needsHostname = true;
            }

            if ($needsHostname) {
                if ('' !== $host || ('' !== $scheme && 'http' !== $scheme && 'https' !== $scheme)) {
                    $port = '';
                    if ('http' === $scheme && 80 !== $this->context->getHttpPort()) {
                        $port = ':'.$this->context->getHttpPort();
                    } elseif ('https' === $scheme && 443 !== $this->context->getHttpsPort()) {
                        $port = ':'.$this->context->getHttpsPort();
                    }

                    $schemeAuthority = self::NETWORK_PATH === $referenceType || '' === $scheme ? '//' : "$scheme://";
                    $schemeAuthority .= $host.$port;
                }
            }

            $qs = http_build_query($parameters);
            if ($qs) {
                $qs = '?' . $qs;
            }

            return $schemeAuthority . $this->context->getBaseUrl() . $path . $qs;
        }
        if ($element instanceof Concrete) {
            $linkGenerator = $element->getClass()->getLinkGenerator();
            if ($linkGenerator) {
                return $linkGenerator->generate($element, [
                    'route' => $this->getCurrentRoute(),
                    'parameters' => $parameters,
                    'context' => $this,
                    'referenceType' => $referenceType,
                ]);
            }
        }

        if ($element instanceof ElementInterface) {
            throw new RouteNotFoundException(
                sprintf(
                    'Could not generate URL for element (Type: %s, ID: %d)',
                    $element->getType(),
                    $element->getId()
                )
            );
        }

        throw new RouteNotFoundException('Could not generate URL for non elements');
    }

    /**
     * Tries to get the current route name from current or master request
     *
     * @return string|null
     */
    protected function getCurrentRoute()
    {
        $route = null;

        if ($this->requestHelper->hasCurrentRequest()) {
            $route = $this->requestHelper->getCurrentRequest()->attributes->get('_route');
        }

        if (!$route && $this->requestHelper->hasMainRequest()) {
            $route = $this->requestHelper->getMainRequest()->attributes->get('_route');
        }

        return $route;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function matchRequest(Request $request)// : array
    {
        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $request->getPathInfo()));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function match($pathinfo)// : array
    {
        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    /**
     * {@inheritdoc}
     *
     * @return RouteCollection
     */
    public function getRouteCollection()// : RouteCollection
    {
        return new RouteCollection();
    }
}
