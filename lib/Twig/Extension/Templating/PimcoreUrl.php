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

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Http\RequestHelper;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class PimcoreUrl implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    protected UrlGeneratorInterface $generator;

    protected RequestHelper $requestHelper;

    public function __construct(UrlGeneratorInterface $generator, RequestHelper $requestHelper)
    {
        $this->generator = $generator;
        $this->requestHelper = $requestHelper;
    }

    public function __invoke(array $urlOptions = [], string $name = null, bool $reset = false, bool $encode = true, bool $relative = false): string
    {
        // merge all parameters from request to parameters
        if (!$reset && $this->requestHelper->hasMainRequest()) {
            $urlOptions = array_replace($this->requestHelper->getMainRequest()->query->all(), $urlOptions);
        }

        return $this->generateUrl($name, $urlOptions, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH, $encode);
    }

    /**
     * Generate URL with support to only pass parameters ZF1 style (defaults to current route).
     *
     *
     */
    protected function generateUrl(array|string $name = null, ?array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, bool $encode = true): string
    {
        if ($encode !== true) {
            // encoding is default anyway, so we only set it when really necessary, to minimize the risk of
            // side-effects when using parameters for that purpose (other routers may not be aware of param `encode`
            $parameters['encode'] = $encode;
        }

        // if name is an array, treat it as parameters
        if (is_array($name)) {
            if (is_array($parameters)) {
                $parameters = array_merge($name, $parameters);
            } else {
                $parameters = $name;
            }

            $name = null;
        }

        // get name from current route
        if (null === $name) {
            $name = $this->getCurrentRoute();
        }

        $object = $parameters['object'] ?? null;
        $linkGenerator = null;

        if ($object) {
            if (method_exists($object, 'getClass') && method_exists($object->getClass(), 'getLinkGenerator')) {
                $linkGenerator = $object->getClass()->getLinkGenerator();
            } elseif (method_exists($object, 'getLinkGenerator')) { // useful for ecommerce LinkGeneratorAwareInterface
                $linkGenerator = $object->getLinkGenerator();
            }
        }

        if ($linkGenerator) {
            if (array_key_exists('object', $parameters)) {
                unset($parameters['object']);
            }
            $path = $linkGenerator->generate($object, [
                'route' => $name,
                'parameters' => $parameters,
                'context' => $this,
                'referenceType' => $referenceType,
            ]);

            return $path;
        }

        if ($name !== null) {
            return $this->generator->generate($name, $parameters, $referenceType);
        }

        return '';
    }

    /**
     * Tries to get the current route name from current or main request
     *
     */
    protected function getCurrentRoute(): ?string
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
}
