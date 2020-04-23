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

/**
 * A custom router implementation handling pimcore elements.
 */
class Router implements RequestMatcherInterface, VersatileGeneratorInterface
{
    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    public function __construct(RequestContext $context, RequestHelper $requestHelper)
    {
        $this->context = $context;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritDoc
     */
    public function supports($name)
    {
        return $name instanceof ElementInterface;
    }

    /**
     * @inheritDoc
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        if ($name instanceof ElementInterface) {
            return sprintf('Element (Type: %s, ID: %d)', $name->getType(), $name->getId());
        }

        return 'No element';
    }

    /**
     * @inheritDoc
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if ($name instanceof Document || $name instanceof Asset) {
            return $name->getFullPath();
        }
        if ($name instanceof Concrete) {
            $linkGenerator = $name->getClass()->getLinkGenerator();
            if ($linkGenerator) {
                return $linkGenerator->generate($name, [
                    'route' => $this->getCurrentRoute(),
                    'parameters' => $parameters,
                    'context' => $this,
                    'referenceType' => $referenceType,
                ]);
            }
        }

        if ($name instanceof ElementInterface) {
            throw new RouteNotFoundException(
                sprintf(
                    'Could not generate URL for element (Type: %s, ID: %d)',
                    $name->getType(),
                    $name->getId()
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

        if (!$route && $this->requestHelper->hasMasterRequest()) {
            $route = $this->requestHelper->getMasterRequest()->attributes->get('_route');
        }

        return $route;
    }

    public function matchRequest(Request $request)
    {
        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $request->getPathInfo()));
    }
}
